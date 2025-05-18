<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '../includes/db.php';          // Connexion DB en premier !
require_once '../includes/functions.php';   // Puis fonctions qui utilisent $pdo
require_once '../includes/header.php';
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ?");
$stmt->execute([$_SESSION['user']['id']]);
$users = $stmt->fetchAll();

$prefilledId = isset($_GET['to']) && is_numeric($_GET['to']) ? (int) $_GET['to'] : null;
$usernamePrefilled = null;

if ($prefilledId) {
    $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmtUser->execute([$prefilledId]);
    $row = $stmtUser->fetch();
    if ($row) {
        $usernamePrefilled = $row['username'];
    }
}

$customHeadStyle = <<<CSS
    body {
            background-color: #121212;
            color: #f0f0f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #1e1e1e;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(255,165,0,0.3);
        }
        .message-header {
            background: rgba(255,165,0,0.1);
            border: 1px solid rgba(255,165,0,0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            color: #ffad33;
            text-align: center;
            font-weight: 600;
            font-size: 1.8rem;
            letter-spacing: 1px;
            text-shadow: 0 0 5px #ffae33bb;
        }
        .select-box {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            background: #121212;
            border: 2px solid #ffad33;
            border-radius: 10px;
            color: #f0f0f0;
            font-size: 1.1rem;
            transition: border-color 0.3s ease;
        }
        .select-box:focus {
            border-color: #ffaa00;
            outline: none;
            box-shadow: 0 0 8px #ffaa00aa;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(90deg, #ff9500, #ffad33);
            border: none;
            border-radius: 12px;
            color: #121212;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
        }
        .button:hover {
            background: linear-gradient(90deg, #ffaa00, #ffc966);
        }
        p, strong {
            font-size: 1.1rem;
        }
        .info-text {
            margin-bottom: 25px;
            font-weight: 600;
            color: #ffd88a;
            text-align: center;
            font-size: 1.2rem;
        }
        .btn-back {
            margin-top: 30px;
            text-align: center;
        }
        .btn-back a {
            color: #ffad33;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            border: 2px solid #ffad33;
            padding: 8px 16px;
            border-radius: 10px;
            display: inline-block;
            transition: background 0.3s ease;
        }
        .btn-back a:hover {
            background: #ffad33;
            color: #121212;
            text-shadow: none;
        }
CSS;
    
    
    
    
?>

<!DOCTYPE html>
<html lang="fr">
    <?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container">
    <div class="message-header">✉️ Choisir un destinataire</div>

    <?php if ($prefilledId && $usernamePrefilled): ?>
        <form method="GET" action="../views/send.php" style="text-align:center;">
            <input type="hidden" name="id" value="<?= $prefilledId ?>">
            <p class="info-text">
                Destinataire : <strong><?= htmlspecialchars($usernamePrefilled) ?></strong>
            </p>
            <button type="submit" class="button">✉️ Envoyer un message</button>
        </form>
    <?php elseif (empty($users)): ?>
        <p style="text-align:center; font-size:1.2rem;">Aucun autre utilisateur disponible.</p>
    <?php else: ?>
        <form method="GET" action="../views/send.php" style="text-align:center;">
            <select name="id" class="select-box" required>
                <option value="">-- Sélectionner un destinataire --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>">
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            <button type="submit" class="button">Continuer ➡️</button>
        </form>
    <?php endif; ?>

    <div class="btn-back">
        <a href="/ProjetPrison/views/inbox.php">🔙 Retour à la boîte de réception</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
