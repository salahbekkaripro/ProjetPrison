<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';
// Vérifie que seul le rôle 'admin' peut accéder
checkRole('admin');


$pageTitle = "Tableau de Bord - Admin";

// Récupérer les statistiques
$total_prisonniers = $pdo->query("SELECT COUNT(*) FROM prisonnier")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'gardien'")->fetchColumn();
$total_cellules = $pdo->query("SELECT COUNT(*) FROM cellule")->fetchColumn();
$total_infractions = $pdo->query("SELECT COUNT(*) FROM infraction")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>



<body><?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2 style="text-align:center; margin-top: 20px;">Tableau de Bord - Admin</h2>

    <div class="stats-container">
        <div class="stat-box">
            <h3>Total Prisonniers</h3>
            <p><?= $total_prisonniers ?></p>
        </div>
        <div class="stat-box">
            <h3>Total admins</h3>
            <p><?= $total_admins ?></p>
        </div>
        <div class="stat-box">
            <h3>Cellules Disponibles</h3>
            <p><?= $total_cellules ?></p>
        </div>
        <div class="stat-box">
            <h3>Total Infractions</h3>
            <p><?= $total_infractions ?></p>
        </div>
    </div>

    <div style="max-width: 600px; margin: auto;">
        <canvas id="chartPrisonniers" width="400" height="200"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('chartPrisonniers').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Prisonniers', 'admins', 'Cellules', 'Infractions'],
                datasets: [{
                    label: 'Statistiques Générales',
                    data: [<?= $total_prisonniers ?>, <?= $total_admins ?>, <?= $total_cellules ?>, <?= $total_infractions ?>],
                    backgroundColor: ['blue', 'green', 'orange', 'red'],
                }]
            }
        });
    </script>

    <div class="admin-actions" style="text-align:center; margin-top: 40px;">
        <a href="surveillance_cellule.php" class="sort-btn">Gérer les Cellules</a>
        <a href="infractions_admin.php" class="sort-btn">Voir les Infractions</a>
        <a href="planning_utilisateur.php" class="sort-btn">Gérer les Plannings</a>
    </div>
</div>

</body>
</html>
