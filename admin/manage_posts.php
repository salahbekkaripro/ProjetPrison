<?php
session_start();
$pageTitle = "Gestion des messages";
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_role.php';
require_once '../includes/header.php';
checkRole('admin');

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    header('Location: manage_posts.php');
    exit;
}

$posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
$customHeadStyle = <<<CSS
.container {
    max-width: 1000px;
    margin: 50px auto;
}
.section-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    color: white;
}
.post-item {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}
.post-title {
    color: #ffaa55;
    font-size: 1.4rem;
    font-weight: bold;
}
.post-content {
    color: white;
    margin: 10px 0;
}
.post-date {
    color: #aaa;
    font-size: 0.9em;
}
.button-group a {
    margin-right: 10px;
    padding: 8px 15px;
    display: inline-block;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 10px;
    color: white;
    text-decoration: none;
    font-size: 0.9rem;
}
.button-group a:hover {
    background: rgba(255,165,0,0.2);
}
.delete-btn {
    background: rgba(255,0,0,0.2);
    border-color: rgba(255,0,0,0.4);
}
.delete-btn:hover {
    background: rgba(255,0,0,0.4);
}
CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>

<?php include '../includes/navbar.php'; ?>


<div class="container">
    <div class="section-box">
        <h2>Bienvenue, <span style="color: #ff1919;"> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?> </span></h2>
        <h3 style="margin-top: 15px; color: #ff1919;">📝 Messages postés :</h3>
        <p style="margin-top: 15px;">
            <a href="new_post.php" class="button-group">📄 Ajouter un nouveau message</a>
        </p>
    </div>

    <?php if (empty($posts)) : ?>
        <p style="color:white; text-align:center;">Aucun message pour l’instant.</p>
    <?php else : ?>
        <?php foreach ($posts as $post) : ?>
            <div class="post-item">
                <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
                <div class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
                <div class="post-date">🕓 Posté le <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
                <div class="button-group" style="margin-top:10px;">
                    <a href="edit_post.php?id=<?= $post['id'] ?>">✏️ Modifier</a>
                    <a href="manage_posts.php?delete=<?= $post['id'] ?>" onclick="return confirm('Supprimer ce post ?');" class="delete-btn">🗑 Supprimer</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
