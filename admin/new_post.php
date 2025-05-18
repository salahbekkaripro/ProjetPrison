<?php
session_start();
$pageTitle = "Ajouter un message";
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_role.php';
require_once '../includes/header.php';
// Vérifie que seul le rôle 'admin' peut accéder
checkRole('admin');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $error = "Tous les champs sont requis.";
    } else {
        $author = $_SESSION['user']['username'] ?? 'Anonyme';
        $isApproved = ($_SESSION['user']['role'] === 'admin') ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO posts (title, content, author, is_approved) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $author, $isApproved]);

        $success = "Message publié avec succès !";
    }
}

    
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<body>
<?php include '../includes/navbar.php'; ?>
<div id="page-transition"></div>

<div id="app-content">
    <div class="section-box" style="max-width: 800px; margin: 50px auto; padding: 30px;">

        <h2 style="color: #ffaa55; font-size: 1.8rem; margin-bottom: 20px;">📝 Ajouter un nouveau message</h2>

        <?php if ($error): ?>
            <div style="color:red; margin-bottom:15px;"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div style="color:limegreen; margin-bottom:15px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" style="display: flex; flex-direction: column; gap: 20px;">

            <div>
                <label for="title" style="color: #ffaa55;">Titre du message :</label><br>
                <input type="text" name="title" id="title" required
                       style="width: 100%; margin-top: 5px; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,165,0,0.2); border-radius: 12px; color: white;">
            </div>

            <div>
                <label for="content" style="color: #ffaa55;">Contenu :</label><br>
                <textarea name="content" id="content" rows="6" required
                          style="width: 100%; margin-top: 5px; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,165,0,0.2); border-radius: 12px; color: white;"></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" style="padding: 10px 20px; margin-top: 10px;
                        background: rgba(255,255,255,0.1); border: none; border-radius: 8px; color: white; cursor: pointer; font-size: 1em;">
                    📄 Publier
                </button>
            </div>

        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="manage_posts.php" style="display: inline-block; padding: 10px 20px;
                    background: rgba(255,255,255,0.1); border-radius: 8px; color: white; text-decoration: none; font-size: 1em; margin-top: 10px;">
                ⬅️ Retour à la gestion
            </a>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
