<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

// Vérifie que seul le rôle 'gestionnaire' peut accéder
checkRole('gestionnaire');

if (!in_array($_SESSION['user']['role'], ['gestionnaire', 'chef'])) {
    echo "⛔ Accès interdit.";
    exit;
}

$pageTitle = "📊 Rapport Journalier";

// 📅 Date sélectionnée ou aujourd'hui
$date = $_GET['date'] ?? date('Y-m-d');
$formatted = date('d/m/Y', strtotime($date));

// 📤 Fonction export CSV
function exportCSV($rows, $filename, $columns) {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"$filename.csv\"");
    $output = fopen("php://output", "w");
    fputcsv($output, $columns);
    foreach ($rows as $row) {
        fputcsv($output, array_map(fn($k) => $row[$k] ?? '', $columns));
    }
    fclose($output);
    exit;
}

// 🔁 Exportation CSV déclenchée
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];

    if ($exportType === 'infractions') {
        $stmt = $pdo->prepare("SELECT i.*, u.nom, u.prenom FROM infraction i JOIN prisonnier p ON i.prisonnier_id = p.id JOIN users u ON p.utilisateur_id = u.id WHERE DATE(i.date_infraction) = ?");
        $stmt->execute([$date]);
        exportCSV($stmt->fetchAll(), "infractions_$date", ['prenom', 'nom', 'type_infraction', 'date_infraction']);
    }

    if ($exportType === 'pots') {
        $stmt = $pdo->prepare("SELECT pd.*, gu.nom AS gardien_nom, gu.prenom AS gardien_prenom, pu.nom AS prisonnier_nom, pu.prenom AS prisonnier_prenom FROM pots_de_vin pd JOIN gardien g ON pd.admin_id = g.id JOIN users gu ON g.utilisateur_id = gu.id JOIN prisonnier p ON pd.prisonnier_id = p.id JOIN users pu ON p.utilisateur_id = pu.id WHERE DATE(pd.date_demande) = ?");
        $stmt->execute([$date]);
        exportCSV($stmt->fetchAll(), "pots_de_vin_$date", ['gardien_prenom', 'gardien_nom', 'prisonnier_prenom', 'prisonnier_nom', 'montant', 'statut']);
    }

    if ($exportType === 'sanctions') {
        $stmt = $pdo->prepare("SELECT s.*, u.nom, u.prenom FROM sanction s JOIN prisonnier p ON s.prisonnier_id = p.id JOIN users u ON p.utilisateur_id = u.id WHERE DATE(s.date_sanction) = ?");
        $stmt->execute([$date]);
        exportCSV($stmt->fetchAll(), "sanctions_$date", ['prenom', 'nom', 'type_sanction', 'gravite', 'duree_jours']);
    }

    if ($exportType === 'objets') {
        $stmt = $pdo->prepare("SELECT * FROM objets_disponibles WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        exportCSV($stmt->fetchAll(), "objets_$date", ['nom', 'description', 'prix', 'interdit']);
    }
}

// 🔍 Récupération des données
$infractions = $pdo->prepare("SELECT i.*, u.nom, u.prenom FROM infraction i JOIN prisonnier p ON i.prisonnier_id = p.id JOIN users u ON p.utilisateur_id = u.id WHERE DATE(i.date_infraction) = ?");
$infractions->execute([$date]);
$infractions = $infractions->fetchAll();

$pots = $pdo->prepare("SELECT pd.*, gu.nom AS gardien_nom, gu.prenom AS gardien_prenom, pu.nom AS prisonnier_nom, pu.prenom AS prisonnier_prenom FROM pots_de_vin pd JOIN gardien g ON pd.admin_id = g.id JOIN users gu ON g.utilisateur_id = gu.id JOIN prisonnier p ON pd.prisonnier_id = p.id JOIN users pu ON p.utilisateur_id = pu.id WHERE DATE(pd.date_demande) = ?");
$pots->execute([$date]);
$pots = $pots->fetchAll();

$sanctions = $pdo->prepare("SELECT s.*, u.nom, u.prenom FROM sanction s JOIN prisonnier p ON s.prisonnier_id = p.id JOIN users u ON p.utilisateur_id = u.id WHERE DATE(s.date_sanction) = ?");
$sanctions->execute([$date]);
$sanctions = $sanctions->fetchAll();

$objets = $pdo->prepare("SELECT * FROM objets_disponibles WHERE DATE(created_at) = ?");
$objets->execute([$date]);
$objets = $objets->fetchAll();

$customHeadStyle = <<<CSS

.dashboard-container { padding: 20px; max-width: 1000px; margin: auto; }
        .rapport-section { margin-top: 30px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 6px; }
        .btn-sm {
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.9rem;
            margin-left: 10px;
            cursor: pointer;
        }

CSS;

?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>

<body>
<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>📊 Rapport Journalier – <?= $formatted ?></h2>

    <form method="get" style="margin-bottom: 20px;">
        <label>Sélectionner une date :
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
        </label>
        <button type="submit" class="btn-sm">🔍 Voir</button>
    </form>

    <div class="rapport-section">
        <h3>🚨 Infractions
            <a href="?date=<?= $date ?>&export=infractions" class="btn-sm">⬇️ CSV</a>
        </h3>
        <?php if ($infractions): ?>
            <ul>
                <?php foreach ($infractions as $i): ?>
                    <li>🧍 <?= "$i[prenom] $i[nom]" ?> – <?= $i['type_infraction'] ?> à <?= date('H:i', strtotime($i['date_infraction'])) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>✅ Aucune infraction ce jour.</p>
        <?php endif; ?>
    </div>

    <div class="rapport-section">
        <h3>💰 Pots-de-vin
            <a href="?date=<?= $date ?>&export=pots" class="btn-sm">⬇️ CSV</a>
        </h3>
        <?php if ($pots): ?>
            <ul>
                <?php foreach ($pots as $p): ?>
                    <li>👮 <?= "$p[gardien_prenom] $p[gardien_nom]" ?> ⇄ 🧍 <?= "$p[prisonnier_prenom] $p[prisonnier_nom]" ?> – <?= $p['montant'] ?>€ (<?= $p['statut'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>✅ Aucun pot-de-vin ce jour.</p>
        <?php endif; ?>
    </div>

    <div class="rapport-section">
        <h3>⚖️ Sanctions
            <a href="?date=<?= $date ?>&export=sanctions" class="btn-sm">⬇️ CSV</a>
        </h3>
        <?php if ($sanctions): ?>
            <ul>
                <?php foreach ($sanctions as $s): ?>
                    <li>🧍 <?= "$s[prenom] $s[nom]" ?> – <?= $s['type_sanction'] ?> (gravité : <?= $s['gravite'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>✅ Aucune sanction ce jour.</p>
        <?php endif; ?>
    </div>

    <div class="rapport-section">
        <h3>📦 Objets ajoutés
            <a href="?date=<?= $date ?>&export=objets" class="btn-sm">⬇️ CSV</a>
        </h3>
        <?php if ($objets): ?>
            <ul>
                <?php foreach ($objets as $o): ?>
                    <li><?= $o['nom'] ?> – <?= number_format($o['prix'], 2) ?>€ <?= $o['interdit'] ? '(❌ interdit)' : '' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>✅ Aucun objet ajouté ce jour.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
