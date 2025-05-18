<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

checkRole('prisonnier');

$pageTitle = "Détails de la cellule";

$cellule_id = intval($_GET['id'] ?? 0);
if (!$cellule_id) {
    echo "<p style='color:red; text-align:center;'>❌ ID de cellule invalide.</p>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cellule WHERE id = ?");
$stmt->execute([$cellule_id]);
$cellule = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cellule) {
    echo "<p style='color:red; text-align:center;'>❌ Cellule introuvable.</p>";
    exit;
}

// 🔒 Récupérer l'ID du prisonnier depuis l'utilisateur connecté
$user_id = $_SESSION['user']['id'] ?? 0;
$stmt = $pdo->prepare("SELECT id FROM prisonnier WHERE utilisateur_id = ?");
$stmt->execute([$user_id]);
$current_prisonnier_id = $stmt->fetchColumn();
if (!$current_prisonnier_id) {
    echo "<p style='color:red;'>❌ Aucun prisonnier lié à cet utilisateur.</p>";
    exit;
}

// 👥 Prisonniers dans la cellule
$prisonniersStmt = $pdo->prepare("
    SELECT u.nom, u.prenom, p.id AS prisonnier_id, p.etat
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE p.cellule_id = ?
");
$prisonniersStmt->execute([$cellule_id]);
$prisonniers = $prisonniersStmt->fetchAll(PDO::FETCH_ASSOC);

// 🍏 Aliments disponibles
$alimentairesStmt = $pdo->prepare("
    SELECT op.id, op.nom_objet
    FROM objets_prisonniers op
    JOIN objets_disponibles od ON od.id = op.objet_id
    WHERE op.prisonnier_id = ? AND od.type = 'alimentation'
");
$alimentairesStmt->execute([$current_prisonnier_id]);
$aliments = $alimentairesStmt->fetchAll(PDO::FETCH_ASSOC);

// 🍽️ Traitement de l'action "manger"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manger'], $_POST['aliment_id'])) {
    $aliment_id = intval($_POST['aliment_id']);
    $stmtDel = $pdo->prepare("DELETE FROM objets_prisonniers WHERE id = ? AND prisonnier_id = ?");
    $stmtDel->execute([$aliment_id, $current_prisonnier_id]);

    if ($stmtDel->rowCount()) {
        $pdo->prepare("UPDATE prisonnier SET etat = 'sain' WHERE id = ?")->execute([$current_prisonnier_id]);
        header("Location: cellule.php?id=$cellule_id&success=1");
        exit;
    } else {
        header("Location: cellule.php?id=$cellule_id&error=1");
        exit;
    }
}

// 🎯 Feedback
$feedback = '';
$show_heal_animation = false;
if (isset($_GET['success'])) {
    $feedback = "✅ Vous vous sentez mieux après avoir mangé.";
    $show_heal_animation = true;
} elseif (isset($_GET['error'])) {
    $feedback = "❌ Impossible de consommer cet objet.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="glass-box" style="max-width: 900px; margin: 40px auto;">
    <h2 class="text-2xl">🏠 Cellule n°<?= htmlspecialchars($cellule['numero_cellule']) ?></h2>

    <ul>
        <li><strong>Capacité :</strong> <?= htmlspecialchars($cellule['capacite']) ?> prisonnier(s)</li>
        <li><strong>Surveillance :</strong> <?= $cellule['surveillance'] ? 'Oui 🔍' : 'Non ❌' ?></li>
    </ul>

    <h3 style="margin-top: 25px;">👥 Présents dans la cellule :</h3>

    <?php if ($feedback): ?>
        <div class="alert" style="margin-bottom: 20px; color: lime; font-weight: bold;"><?= $feedback ?></div>
    <?php endif; ?>

    <?php if (count($prisonniers) > 0): ?>
        <div class="cellule-grille">
            <?php foreach ($prisonniers as $p): ?>
                <?php
                    $is_self = $p['prisonnier_id'] == $current_prisonnier_id;
                    $classes = "cellule-box" . ($is_self ? " highlight" : "");
                    if ($show_heal_animation && $is_self) {
                        $classes .= " healing-animation";
                    }
                ?>
                <div class="<?= $classes ?>">
                    <p style="font-weight: bold; font-size: 18px;">
                        <?= htmlspecialchars($p['prenom']) ?> <?= htmlspecialchars($p['nom']) ?>
                        <?= $is_self ? '⭐' : '' ?>
                    </p>
                    <p>🩺 État : <strong><?= htmlspecialchars($p['etat']) ?></strong></p>
                    <p>🆔 ID : <?= $p['prisonnier_id'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Aucun prisonnier dans cette cellule.</p>
    <?php endif; ?>

    <!-- 🍽️ Formulaire manger -->
    <div style="text-align: center; margin-top: 40px;">
        <h3>🍽️ Vous avez faim ?</h3>
        <?php if (count($aliments) > 0): ?>
            <form method="POST">
                <select name="aliment_id" required>
                    <?php foreach ($aliments as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nom_objet']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="manger" class="sort-btn">Manger 🍎</button>
            </form>
        <?php else: ?>
            <p style="color: gray;">Vous n'avez aucun aliment.</p>
            <p><a href="acheter_objet.php" class="sort-btn">🛒 Acheter un aliment</a></p>
        <?php endif; ?>
    </div>

    <div style="text-align:center; margin-top: 30px;">
        <a href="dashboard_prisonnier.php" class="sort-btn">⬅️ Retour au tableau de bord</a>
    </div>
</div>
</body>
</html>
