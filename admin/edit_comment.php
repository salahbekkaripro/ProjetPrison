<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_role.php';
require_once '../includes/header.php';
checkRole('admin');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de commentaire invalide.");
}

$id = (int) $_GET['id'];

// 🔎 Récupérer le commentaire
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$id]);
$comment = $stmt->fetch();

if (!$comment) {
    die("Commentaire introuvable.");
}

$error = '';
$success = '';

// 📝 Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = trim($_POST['author'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($author) || empty($content)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $update = $pdo->prepare("UPDATE comments SET author = ?, content = ? WHERE id = ?");
        $update->execute([$author, $content, $id]);
        
        // Redirection avec message flash
        $_SESSION['flash_success'] = "Commentaire modifié avec succès.";
        header('Location: manage_comments.php');
        exit;
        
    }
}

$pageTitle = "Modifier un commentaire";
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<body>
<?php include '../includes/navbar.php'; ?>
<div id="page-transition"></div>


<h2>Modifier un commentaire</h2>

<?php if ($error): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php elseif ($success): ?>
    <p style="color: green;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<form method="post">
    <label>Auteur :</label><br>
    <input type="text" name="author" value="<?= htmlspecialchars($comment['author']) ?>" required><br><br>

    <label>Contenu :</label><br>
    <textarea name="content" rows="5" required><?= htmlspecialchars($comment['content']) ?></textarea><br><br>

    <button type="submit">💾 Enregistrer</button>
</form>

<p><a href="manage_comments.php">← Retour à la gestion</a></p>

<?php include '../includes/footer.php'; ?>
</body>
</html>
