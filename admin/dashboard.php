<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_role.php';
require_once '../includes/header.php';
// Vérifie que seul le rôle 'admin' peut accéder
checkRole('admin');


$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalReported = $pdo->query("SELECT COUNT(*) FROM comments WHERE reported = 1")->fetchColumn();
$totalAdminReplies = $pdo->query("SELECT COUNT(*) FROM comments WHERE author = '[ADMIN]'")->fetchColumn();
$topTags = $pdo->query("
    SELECT tag, COUNT(*) as count 
    FROM comments 
    WHERE tag IS NOT NULL AND tag != '' 
    GROUP BY tag 
    ORDER BY count DESC 
    LIMIT 5
")->fetchAll();

$pageTitle = "Tableau de bord";

$customHeadStyle = <<<CSS
.container {
    max-width: 1200px;
    margin: 50px auto;
}
.dashboard-header, .dashboard-stats {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
}
.dashboard-header h2 {
    font-size: 2.5rem;
    font-weight: bold;
    text-align: center;
}
.stat-item {
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.stat-item:last-child {
    border-bottom: none;
}
.top-tags {
    margin-top: 20px;
}
.top-tags h4 {
    color: #ff1919;
    margin-bottom: 10px;
}
.top-tags ul {
    list-style: none;
    padding: 0;
}
.top-tags li {
    background: rgba(255,25,25,0.05);
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.top-tags span.tag-name {
    color: #ffa500;
    font-weight: bold;
}
.top-tags span.tag-count {
    color: #ccc;
}

CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>📊 Tableau de bord admin</h2>
    </div>

    <div class="dashboard-stats">
        <div class="stat-item"><strong>Total messages :</strong> <?= $totalPosts ?></div>
        <div class="stat-item"><strong>Total commentaires :</strong> <?= $totalComments ?></div>
        <div class="stat-item"><strong>Commentaires signalés :</strong> <?= $totalReported ?></div>
        <div class="stat-item"><strong>Réponses admin :</strong> <?= $totalAdminReplies ?></div>

        <div class="top-tags">
            <h4>📌 Tags les plus utilisés :</h4>
            <ul>
                <?php foreach ($topTags as $tag): ?>
                    <li>
                        <span class="tag-name">🏷 <?= htmlspecialchars($tag['tag']) ?></span>
                        <span class="tag-count">&times; <?= $tag['count'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</html>
