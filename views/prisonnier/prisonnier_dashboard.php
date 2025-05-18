<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/check_role.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

// Vérifie que seul le rôle 'prisonnier' peut accéder
checkRole('prisonnier');

$pageTitle = "Tableau de Bord - Prisonnier";

// 🔐 Vérification de l'utilisateur
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    echo "⛔ Accès interdit.";
    exit;
}

$user_id = $_SESSION['user']['id'];

// 🔍 Infos du prisonnier
$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.nom, u.prenom, u.created_at, u.status,
           p.id AS prisonnier_id, p.date_entree, p.date_sortie, p.motif_entree, 
           p.etat, p.objet, p.cellule_id
    FROM users u
    LEFT JOIN prisonnier p ON p.utilisateur_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$infos = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Enregistre l'ID du prisonnier dans la session
if ($infos && isset($infos['prisonnier_id'])) {
    $_SESSION['user']['prisonnier_id'] = $infos['prisonnier_id'];
}

// 🔔 Récupération des notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC");
$notifStmt->execute([$user_id]);
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
$customHeadStyle = <<<CSS
.link-style {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .link-style:hover {
            text-decoration: underline;
        }


CSS;
    
    
    
    
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>


<body>
    <?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2 style="text-align:center; margin-top: 20px;">Tableau de Bord - Prisonnier</h2>


    <!-- 📊 Infos -->
    <div class="stats-container">
        <div class="stat-box">
            <h3>Nom complet</h3>
            <p><?= htmlspecialchars($infos['nom'] . ' ' . $infos['prenom']) ?></p>
        </div>

        <div class="stat-box">
            <h3>Cellule</h3>
            <?php if ($infos['cellule_id']): ?>
                <p>
                    <a href="cellule.php?id=<?= $infos['cellule_id'] ?>" class="link-style">
                        🔗 Cellule n°<?= $infos['cellule_id'] ?>
                    </a>
                </p>
            <?php else: ?>
                <p>Non affectée</p>
            <?php endif; ?>
        </div>

<?php
// Récupère les objets détenus depuis objets_prisonniers
$objStmt = $pdo->prepare("
    SELECT op.nom_objet 
    FROM objets_prisonniers op
    WHERE op.prisonnier_id = ?
");
$objStmt->execute([$_SESSION['user']['prisonnier_id']]);
$objets = $objStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<?php
// Récupère les objets détenus depuis objets_prisonniers
$objStmt = $pdo->prepare("
    SELECT op.nom_objet, op.id
    FROM objets_prisonniers op
    WHERE op.prisonnier_id = ?
");
$objStmt->execute([$_SESSION['user']['prisonnier_id']]);
$objets = $objStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="stat-box">
    <h3>Objets détenus</h3>
    <?php if (!empty($objets)): ?>
        <ul>
            <?php foreach ($objets as $o): ?>
                <li>
                    <a href="objet.php?id=<?= $o['id'] ?>" class="link-style">
                        <?= htmlspecialchars($o['nom_objet']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun</p>
    <?php endif; ?>
</div>


        <div class="stat-box">
            <h3>État</h3>
            <p><?= htmlspecialchars($infos['etat']) ?></p>
        </div>
    </div>

    <!-- ⚙️ Actions -->
    <div class="admin-actions" style="text-align:center; margin-top: 40px;">
        <a href="../profil.php" class="sort-btn">👤 Voir le Profil</a>
        <a href="../inbox.php" class="sort-btn">📨 Messagerie</a>
        <a href="../logout.php" class="sort-btn">🚪 Déconnexion</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>

<script>
function repondrePotDeVin(accepte, potId) {
    fetch('../../ajax/repondre_pot_de_vin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accepte: accepte, pot_id: potId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("✅ Réponse envoyée !");
            location.reload();
        } else {
            alert("❌ " + data.error);
        }
    });
}
</script>
