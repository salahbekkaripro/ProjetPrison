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


$current_user_id = $_SESSION['user']['id'];
$success = $error = null;

// 🎯 Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['infraction_id'])) {
    $infraction_id = intval($_POST['infraction_id']);
    $type = trim($_POST['type_sanction']);
    $gravite = $_POST['gravite'] ?? 'moyenne';
    $duree = intval($_POST['duree_jours'] ?? 0);
$commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

    $stmt = $pdo->prepare("SELECT prisonnier_id FROM infraction WHERE id = ?");
    $stmt->execute([$infraction_id]);
    $inf = $stmt->fetch();

    if ($inf) {
        $prisonnier_id = $inf['prisonnier_id'];

        // Récupération de l'utilisateur du prisonnier
        $stmt = $pdo->prepare("SELECT u.id AS user_id, u.nom, u.prenom, u.argent FROM prisonnier p JOIN users u ON p.utilisateur_id = u.id WHERE p.id = ?");
        $stmt->execute([$prisonnier_id]);
        $prisonnier = $stmt->fetch();

        if (!$prisonnier) {
            $error = "❌ Prisonnier non trouvé.";
        } else {
            $nomComplet = $prisonnier['prenom'] . ' ' . $prisonnier['nom'];
            $user_id = $prisonnier['user_id'];
            $solde = floatval($prisonnier['argent']);
            $fin_sanction = null;

            if ($type === 'aucune') {
                $pdo->prepare("DELETE FROM infraction WHERE id = ?")->execute([$infraction_id]);
// 🔔 Notif au prisonnier
$pdo->prepare("INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
               VALUES (?, ?, 'sanction_appliquee', ?, 0, NOW())")
    ->execute([
        $user_id,
        $current_user_id,
        "🛑 Votre infraction a été ignorée par le gestionnaire. Aucune sanction ne sera appliquée."
    ]);

// 🔔 Notif au gestionnaire
$pdo->prepare("INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
               VALUES (?, ?, 'sanction_appliquee', ?, 0, NOW())")
    ->execute([
        $current_user_id,
        $current_user_id,
        "ℹ️ Sanction ignorée pour le prisonnier $nomComplet."
    ]);

$success = "ℹ️ Sanction ignorée pour $nomComplet.";
            } else {
                // Amende trop élevée
                if ($type === 'amende' && $duree > $solde) {
                    $error = "💸 Le prisonnier $nomComplet n'a pas assez d'argent ($solde €). Choisissez un autre montant ou une autre sanction.";
                } else {
                    if ($type === 'mise_au_trou') {
                        $fin_sanction = (new DateTime())->modify("+$duree days")->format('Y-m-d H:i:s');
                    }

                    $pdo->prepare("
                        INSERT INTO sanction (infraction_id, prisonnier_id, type_sanction, gravite, duree_jours, commentaire, fin_sanction)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$infraction_id, $prisonnier_id, $type, $gravite, $duree, $commentaire, $fin_sanction]);

                    if ($type === 'amende') {
                        $pdo->prepare("UPDATE users SET argent = GREATEST(argent - ?, 0) WHERE id = ?")
                            ->execute([$duree, $user_id]);
                    }

                    $emoji = $type === 'amende' ? "💸" : ($type === 'mise_au_trou' ? "🔒" : "📛");
                    $pdo->prepare("INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
                                   VALUES (?, ?, 'sanction_appliquee', ?, 0, NOW())")
                        ->execute([$user_id, $current_user_id, "$emoji " . ucfirst($type) . " appliquée à $nomComplet."]);

                    $pdo->prepare("INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
                                   VALUES (?, ?, 'sanction_appliquee', ?, 0, NOW())")
                        ->execute([$current_user_id, $current_user_id, "✅ Sanction $type infligée à $nomComplet."]);

                    $success = "✅ Sanction appliquée à $nomComplet.";
                }
            }
        }
    } else {
        $error = "❌ Infraction introuvable.";
    }
}

// 📥 Liste des prisonniers
$listePrisonniers = $pdo->query("
    SELECT p.id, u.nom, u.prenom 
    FROM prisonnier p 
    JOIN users u ON u.id = p.utilisateur_id 
    ORDER BY u.nom
")->fetchAll(PDO::FETCH_ASSOC);

// 📥 Filtrage si demandé
$filtre = isset($_GET['filtre_prisonnier']) ? intval($_GET['filtre_prisonnier']) : null;

$sql = "
    SELECT i.id, i.type_infraction, i.date_infraction, p.id AS prisonnier_id, u.nom, u.prenom
    FROM infraction i
    LEFT JOIN sanction s ON s.infraction_id = i.id
    JOIN prisonnier p ON i.prisonnier_id = p.id
    JOIN users u ON p.utilisateur_id = u.id
    WHERE s.id IS NULL
";
$params = [];

if ($filtre) {
    $sql .= " AND p.id = ?";
    $params[] = $filtre;
}

$sql .= " ORDER BY i.date_infraction DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$infractions = $stmt->fetchAll(PDO::FETCH_ASSOC);


$customHeadStyle = <<<CSS
        .container { max-width: 1000px; margin: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; vertical-align: top; }
        th { background: #8B0000; color: white; }
        input, select, textarea { width: 100%; padding: 6px; margin-top: 4px; }
        button { padding: 6px 12px; margin-top: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }



CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>

<body>

<?php include '../../includes/navbar.php'; ?>

<div class="container">
    <h2>📂 Sanctions à attribuer</h2>

    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="get">
        <label for="filtre_prisonnier">🔍 Filtrer par prisonnier :</label>
        <select name="filtre_prisonnier" onchange="this.form.submit()">
            <option value="">-- Tous --</option>
            <?php foreach ($listePrisonniers as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($filtre == $p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($infractions)): ?>
        <p>Aucune infraction en attente de sanction.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Prisonnier</th><th>Infraction</th><th>Date</th><th>Sanction</th></tr>
            </thead>
            <tbody>
                <?php foreach ($infractions as $inf): ?>
                    <tr>
                        <td><?= htmlspecialchars($inf['prenom'] . ' ' . $inf['nom']) ?></td>
                        <td><?= htmlspecialchars($inf['type_infraction']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($inf['date_infraction'])) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="infraction_id" value="<?= $inf['id'] ?>">

                                <label>Sanction :</label>
                                <select name="type_sanction" required>
                                    <option value="mise_au_trou">Mise au trou</option>
                                    <option value="amende">Amende (€)</option>
                                    <option value="retrait_activite">Retrait activité</option>
                                    <option value="aucune">Aucune sanction</option>
                                </select>

                                <label>Gravité :</label>
                                <select name="gravite">
                                    <option value="faible">Faible</option>
                                    <option value="moyenne">Moyenne</option>
                                    <option value="grave">Grave</option>
                                </select>

                                <label>Durée ou montant (€) :</label>
                                <input type="number" name="duree_jours" min="0" value="1" required>

                                <label>Commentaire :</label>
                                <textarea name="commentaire" rows="2" placeholder="Motif ou remarque..."></textarea>

<div style="display: flex; flex-direction: column; gap: 6px;">
    <button type="submit">✅ Appliquer</button>
</div>
</form>
<!-- Nouveau formulaire dédié pour ignorer -->
<form method="POST" class="ignore-form" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir ignorer cette infraction ?');">
    <input type="hidden" name="infraction_id" value="<?= $inf['id'] ?>">
    <input type="hidden" name="type_sanction" value="aucune">
    <button type="submit" class="btn-ignorer">
        🗑️ Ignorer la sanction
    </button>
</form>

                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
