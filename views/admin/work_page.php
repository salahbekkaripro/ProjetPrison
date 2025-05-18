<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

checkRole('admin');

$customHeadStyle = <<<CSS
body {
    margin: 0;
    padding: 0;
    background: linear-gradient(145deg, #1c1c1c, #2b2b2b);
    font-family: 'Rajdhani', sans-serif;
    color: #ecf0f1;
    background-image: url('../../assets/prison_bg_dark.jpg'); /* optionnel */
    background-size: cover;
    background-attachment: fixed;
}

.container {
    background: rgba(0, 0, 0, 0.75);
    margin: 60px auto;
    padding: 50px 30px;
    border-radius: 20px;
    box-shadow: 0 0 25px rgba(255, 255, 255, 0.1);
    width: 90%;
    max-width: 1000px;
    text-align: center;
    animation: fadeIn 1.5s ease;
}

h1 {
    font-size: 3rem;
    margin-bottom: 10px;
    color: #ffcc00;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-bottom: 2px solid #ffcc00;
    display: inline-block;
    padding-bottom: 10px;
}

p {
    font-size: 1.4rem;
    margin-bottom: 30px;
    color: #ccc;
}

ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 20px;
}

li {
    background: #2c3e50;
    border-left: 5px solid #ffcc00;
    border-radius: 10px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

li:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(255, 204, 0, 0.2);
}

li a {
    display: block;
    padding: 20px;
    text-decoration: none;
    color: #fff;
    font-size: 1.1rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: background 0.3s ease;
}

li a:hover {
    background: #1a252f;
}

.footer {
    margin-top: 40px;
    padding: 20px;
    background-color: #111;
    color: #999;
    text-align: center;
    font-size: 0.9rem;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="container">
    <h1>Zone Admin</h1>
    <p>Bienvenue, surveillant général. Choisissez une action :</p>

    <ul>
        <li><a href="/ProjetPrison/admin/manage_users.php">👤 Gérer les utilisateurs</a></li>
        <li><a href="/ProjetPrison/admin/validate_post.php">✅ Valider les posts</a></li>
        <li><a href="/ProjetPrison/admin/dashboard.php">📊 Tableau de bord</a></li>
        <li><a href="/ProjetPrison/admin/manage_posts.php">📝 Gérer les posts</a></li>
        <li><a href="/ProjetPrison/admin/manage_comments.php">🚨 Commentaires signalés</a></li>
        <li><a href="/ProjetPrison/views/admin/surveillance_cellule.php">📹 Surveillance cellules</a></li>
        <li><a href="/ProjetPrison/views/admin/infractions_admin.php">⚖️ Infractions</a></li>
        <li><a href="/ProjetPrison/views/admin/planning_utilisateur.php">📅 Plannings</a></li>
        <li><a href="/ProjetPrison/views/admin/ajouter_planning_admin.php">➕ Ajouter un planning</a></li>
        <li><a href="/ProjetPrison/views/admin/logs.php">🗂️ Logs</a></li>
        <li><a href="/ProjetPrison/views/admin/fouille_prisonnier.php">🔍 Fouille prisonniers</a></li>
    </ul>
</div>

<div class="footer">
    &copy; 2025 Prison Management System. Tous droits réservés.
</div>
</body>
</html>
