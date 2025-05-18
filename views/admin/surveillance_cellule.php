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

$prisonniers = $pdo->query("
    SELECT p.id AS pid, u.nom, u.prenom, p.cellule_id, c.numero_cellule AS cellule_nom
    FROM prisonnier p
    JOIN users u ON u.id = p.utilisateur_id
    LEFT JOIN cellule c ON c.id = p.cellule_id
    ORDER BY u.nom
")->fetchAll(PDO::FETCH_ASSOC);

$cellules = $pdo->query("SELECT id, numero_cellule FROM cellule ORDER BY numero_cellule")->fetchAll(PDO::FETCH_ASSOC);

$customHeadStyle = <<<CSS
body {
    background: linear-gradient(to right, #2c3e50, #4ca1af);
    font-family: 'Rajdhani', sans-serif;
    color: #fff;
    margin: 0;
    padding: 0;
}
.dashboard-container {
    max-width: 960px;
    margin: 60px auto;
    padding: 40px;
    background-color: rgba(0,0,0,0.75);
    border-radius: 20px;
    box-shadow: 0 0 30px rgba(0,0,0,0.5);
    animation: fadeIn 1s ease-in;
}
h2, h3 {
    text-align: center;
    margin-bottom: 20px;
}
select {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    border-radius: 10px;
    background-color: #eee;
    color: #111;
    font-size: 1em;
}
.section {
    margin-bottom: 40px;
}
#resultat {
    margin-top: 30px;
}
.btn-action {
    display: inline-block;
    margin: 30px auto 0;
    padding: 14px 28px;
    background-color: #e74c3c;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1em;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.3s;
}
.btn-action:hover {
    background-color: #c0392b;
}
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}
CSS;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include '../../includes/head.php'; ?>
    <style><?= $customHeadStyle ?></style>
    <title>Gestion des cellules</title>
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>🏛️ Gestion des cellules & prisonniers</h2>

    <div class="section">
        <h3>👤 Sélectionner un prisonnier</h3>
        <select id="prisonnier_id">
            <option value="">-- Choisir un prisonnier --</option>
            <?php foreach ($prisonniers as $p): ?>
                <option value="<?= $p['pid'] ?>">
                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?> (Cellule <?= $p['cellule_nom'] ?? 'non affectée' ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="section">
        <h3>🚪 Sélectionner une cellule</h3>
        <select id="cellule_id">
            <option value="">-- Choisir une cellule --</option>
            <?php foreach ($cellules as $c): ?>
                <option value="<?= $c['id'] ?>">Cellule <?= htmlspecialchars($c['numero_cellule']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="resultat"></div>

    <div style="text-align: center;">
        <a href="ajouter_cellule.php" class="btn-action">➕ Ajouter une cellule / Affecter un prisonnier</a>
    </div>
</div>

<script>
document.getElementById('prisonnier_id').addEventListener('change', function () {
    document.getElementById('cellule_id').value = '';
    fetch("../../ajax/ajax_surveillance.php?prisonnier_id=" + this.value)
        .then(res => res.text())
        .then(html => document.getElementById('resultat').innerHTML = html);
});

document.getElementById('cellule_id').addEventListener('change', function () {
    document.getElementById('prisonnier_id').value = '';
    fetch("../../ajax/ajax_surveillance.php?cellule_id=" + this.value)
        .then(res => res.text())
        .then(html => document.getElementById('resultat').innerHTML = html);
});
</script>
</body>
</html>
