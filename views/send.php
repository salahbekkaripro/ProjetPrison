
<?php
// 🔧 Activer les erreurs en développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🧠 Démarrage session & sécurité
session_start();

// 🔌 Chargements essentiels
require_once '../includes/db.php';          // Connexion à la base
require_once '../includes/functions.php';   // Fonctions utilisant $pdo
require_once '../includes/header.php';     // En dernier, pour le header
// 🛡️ Vérification de la session
// 🎯 Récupération des paramètres GET
$receiver_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
$fromProfile = isset($_GET['from_profile']) && is_numeric($_GET['from_profile']) ? (int) $_GET['from_profile'] : null;

// 🚫 Redirection si l'ID est invalide
if (!$receiver_id) {
    header('Location: /ProjetPrison/views/inbox.php');
    exit;
}


$stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmtUser->execute([$receiver_id]);
$userData = $stmtUser->fetch();

if (!$userData) {
    header('Location: /ProjetPrison/views/inbox.php?error=user_not_found');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['receiver_id'], $_POST['subject'], $_POST['content'])) {
        $receiver_id = (int) $_POST['receiver_id'];
        $subject = trim($_POST['subject']);
        $content = trim($_POST['content']);
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        $self_destruct = isset($_POST['self_destruct']) ? 1 : 0;

        if ($receiver_id > 0 && !empty($subject) && !empty($content)) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM private_messages WHERE receiver_id = ?");
            $countStmt->execute([$receiver_id]);
            $messageCount = $countStmt->fetchColumn();

            if ($messageCount >= 50) {
                $deleteOldest = $pdo->prepare("DELETE FROM private_messages WHERE receiver_id = ? ORDER BY created_at ASC LIMIT 1");
                $deleteOldest->execute([$receiver_id]);
            }

            $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, subject, content, created_at, is_anonymous, revealed, self_destruct) VALUES (?, ?, ?, ?, NOW(), ?, 0, ?)");
            $stmt->execute([
                $_SESSION['user']['id'],
                $receiver_id,
                $subject,
                $content,
                $is_anonymous,
                $self_destruct
            ]);

            header('Location: /ProjetPrison/views/inbox.php?sent=1');
            exit;
        }
    }
}

$customHeadStyle = <<<CSS

.container {
    max-width: 800px;
    margin: 50px auto;
}
.message-header {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
    text-align: center;
}
.form-input, .form-textarea {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: none;
    border-radius: 10px;
    padding: 12px;
    color: white;
    margin-bottom: 15px;
    font-size: 16px;
}
.checkbox-group {
    margin: 10px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.checkbox-group label {
    color: white;
    margin: 5px 0;
}
.button {
    padding: 12px 20px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 10px;
    color: white;
    text-decoration: none;
    text-align: center;
    margin-top: 10px;
}
.button:hover {
    background: rgba(255,165,0,0.2);
}

CSS;
require_once '../includes/navbar.php';
  require_once '../includes/header.php';

?>

<div class="container">
    <div class="message-header">
        <h2>📥 Envoyer un message</h2>
        <p style="margin-t
        op:10px;">Destinataire : <strong><?= htmlspecialchars($userData['username']) ?></strong></p>
    </div>

    <form action="send.php?id=<?= $receiver_id ?>" method="POST" enctype="multipart/form-data" style="text-align:center;">
        <input type="hidden" name="receiver_id" value="<?= $receiver_id ?>">
        <input type="text" name="subject" class="form-input" required placeholder="Sujet">
        <textarea name="content" class="form-textarea" required placeholder="Votre message..." rows="5"></textarea>

        <div class="checkbox-group">
            <label><input type="checkbox" name="is_anonymous" value="1"> Envoyer anonymement</label>
            <label><input type="checkbox" name="self_destruct" value="1"> Activer l'autodestruction après lecture</label>
        </div>

        <button type="submit" class="button">Envoyer 🚀</button>
    </form>

    <div style="margin-top:20px; text-align:center;">
        <?php if ($fromProfile): ?>
            <a href="../profil.php?id=<?= $fromProfile ?>" class="button">🔙 Retour au profil</a>
        <?php else: ?>
            <a href="../messages/new_message.php" class="button">🔙 Retour choisir un destinataire</a>
        <?php endif; ?>
    </div>
</div>
