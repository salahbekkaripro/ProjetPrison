<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$error = '';
$success = '';
$pageTitle = "Modifier un message";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_posts.php');
    exit;
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    die("<p style='color:red; text-align:center;'>⛔ Post introuvable.</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
        $success = "Post mis à jour avec succès.";
        $post['title'] = $title;
        $post['content'] = $content;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>
<body style="background-color:#222; font-family: Arial, sans-serif;">

<div class="container" style="max-width: 700px; margin: 40px auto; background: #1c1c1c; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px #ffa500aa;">
    <h2 style="color:#ffa500; text-align:center; margin-bottom: 25px;">✏️ Modifier le message</h2>

    <?php if ($error): ?>
        <p class="error" style="color:#ff5555; font-weight:bold; text-align:center;"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="success" style="color:#28a745; font-weight:bold; text-align:center;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post" novalidate style="display:flex; flex-direction: column; gap: 15px;">
        <label for="title" style="color:#ffa500; font-weight: 700;">Titre</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>" required
            style="padding: 12px; border-radius: 6px; border: 2px solid #ffa500; background: #222; color: #fff; font-size: 1rem;">

        <label for="content" style="color:#ffa500; font-weight: 700;">Contenu</label>
        <textarea id="content" name="content" rows="8" required
            style="padding: 12px; border-radius: 6px; border: 2px solid #ffa500; background: #222; color: #fff; font-size: 1rem;"><?= htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8') ?></textarea>

        <button type="submit"
            style="background: #ffa500; color: #1a1a1a; font-weight: 700; font-size: 1.1rem; padding: 12px; border-radius: 12px; border:none; cursor:pointer; transition: background 0.3s ease;">
            💾 Mettre à jour
        </button>
    </form>

    <div style="text-align:center; margin-top: 20px;">
        <a href="manage_posts.php" class="btn-neon" style="color:#ffa500; font-weight:bold; text-decoration:none; font-size:1rem;">← Retour à la gestion</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
