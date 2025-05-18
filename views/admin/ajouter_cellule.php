<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

checkRole('admin');

// --- Création cellule ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_cellule'])) {
    $numero_cellule = trim($_POST['numero_cellule']);
    if ($numero_cellule !== '') {
        $stmt = $pdo->prepare("INSERT INTO cellule (numero_cellule) VALUES (?)");
        $stmt->execute([$numero_cellule]);
        header("Location: " . $_SERVER['PHP_SELF']); // refresh pour recharger les cellules
        exit;
    }
}

// --- Affectation prisonnier à cellule ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_cellule'])) {
    $prisonnier_id = intval($_POST['prisonnier_id']);
    $cellule_id = intval($_POST['cellule_id']);
    if ($prisonnier_id && $cellule_id) {
        $stmt = $pdo->prepare("UPDATE prisonnier SET cellule_id = ? WHERE id = ?");
        $stmt->execute([$cellule_id, $prisonnier_id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Charger les prisonniers et cellules
$prisonniers = $pdo->query("
    SELECT p.id AS pid, u.nom, u.prenom, p.cellule_id, c.numero_cellule AS cellule_nom
    FROM prisonnier p
    JOIN users u ON u.id = p.utilisateur_id
    LEFT JOIN cellule c ON c.id = p.cellule_id
    ORDER BY u.nom
")->fetchAll(PDO::FETCH_ASSOC);

$cellules = $pdo->query("SELECT id, numero_cellule FROM cellule ORDER BY numero_cellule")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
<?php include '../../includes/navbar.php'; ?>

<style>
    .dashboard-container {
        max-width: 900px; margin: auto; padding: 20px;
    }
    select, button, input[type="text"] {
        padding: 8px 12px; margin: 5px;
    }
    .section { margin-bottom: 30px; }
    table {
        width: 100%; border-collapse: collapse; margin-top: 10px;
    }
    th, td {
        border: 1px solid #aaa; padding: 8px; text-align: center;
    }
    th { background-color: #611; color: white; }
</style>

<div class="dashboard-container">
    <h2 style="text-align:center;">🔐 Gestion des cellules</h2>

    <div class="section">
        <h3>🏗️ Créer une nouvelle cellule</h3>
        <form method="POST">
            <input type="text" name="numero_cellule" placeholder="Numéro de la nouvelle cellule" required>
            <button type="submit" name="new_cellule">➕ Ajouter</button>
        </form>
    </div>

    <div class="section">
        <h3>👥 Affecter un prisonnier à une cellule</h3>
        <form method="POST">
            <select name="prisonnier_id" required>
                <option value="">-- Sélectionner un prisonnier --</option>
                <?php foreach ($prisonniers as $p): ?>
                    <option value="<?= $p['pid'] ?>">
                        <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?> (Cellule <?= $p['cellule_nom'] ?? 'non affectée' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="cellule_id" required>
                <option value="">-- Sélectionner une cellule --</option>
                <?php foreach ($cellules as $c): ?>
                    <option value="<?= $c['id'] ?>">Cellule <?= htmlspecialchars($c['numero_cellule']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_cellule">✅ Affecter</button>
        </form>
    </div>

    <div class="section">
        <h3>📋 Liste actuelle des prisonniers et cellules</h3>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Cellule</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prisonniers as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nom']) ?></td>
                        <td><?= htmlspecialchars($p['prenom']) ?></td>
                        <td><?= htmlspecialchars($p['cellule_nom'] ?? 'Non affectée') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
