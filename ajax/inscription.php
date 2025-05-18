<?php
require_once '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } else {
        // Vérifier si email ou pseudo existe déjà
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetchColumn() > 0) {
            $error = "Ce pseudo ou email est déjà utilisé.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            // Insertion de l'utilisateur dans la base de données
            $insert->execute([$username, $email, $hashedPassword]);
            $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<?php include('../includes/header.php'); ?>

<body>
<div id="page-transition"></div>


<h2>Inscription</h2>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<form method="post">
    <input type="text" name="username" placeholder="Pseudo" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br><br>
    <button type="submit">S'inscrire</button>
</form>

<?php include '../includes/footer.php'; ?>
</body>
</html>
