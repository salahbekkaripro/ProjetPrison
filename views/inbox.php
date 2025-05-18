<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '../includes/db.php';          // Connexion DB en premier !
require_once '../includes/functions.php';   // Puis fonctions qui utilisent $pdo
require_once '../includes/header.php';     // En dernier, pour le header


$user_id = $_SESSION['user']['id'];

$sentSuccess = isset($_GET['sent']) && $_GET['sent'] == 1;
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user_not_found':
            $errorMessage = '🚫 Utilisateur introuvable.';
            break;
    }
}

$stmt = $pdo->prepare("
    SELECT m.*, u.username AS sender_name
    FROM private_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();


    
?>
<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>  

<style>
.container {
    max-width: 900px;
    margin: 50px auto;
}
.inbox-header {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
    text-align: center;
}
.actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 20px;
}
.actions a {
    padding: 10px 20px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    color: white;
    border: 1px solid rgba(255,165,0,0.2);
    text-decoration: none;
}
.message-table {
    width: 100%;
    color: white;
    border-collapse: collapse;
}
.message-table th, .message-table td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.message-table thead {
    background: rgba(0,255,100,0.1);
}
.new-badge {
    color: #00ff99;
    font-weight: bold;
    animation: blink 1s infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
.success-box, .error-box {
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}
.success-box {
    background: rgba(0,255,0,0.1);
    color: #00ff99;
    border: 1px solid #00ff99;
}
.error-box {
    background: rgba(255,0,0,0.1);
    color: #ff4444;
    border: 1px solid #ff4444;
}
</style>

<div class="container">
    <div class="inbox-header">
        <h2>📥 Boîte de réception</h2>
    </div>

    <?php if ($sentSuccess): ?>
        <div id="success-box" class="success-box">✅ Message envoyé avec succès.</div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div id="error-box" class="error-box"><?= $errorMessage ?></div>
    <?php endif; ?>

    <div class="actions">
        <a href="../messages/new_message.php">✉️ Nouveau message</a>
        <a href="../messages/sent.php">📤 Messages envoyés</a>
        <form method="POST" action="/ProjetPrison/messages/delete_all_received.php" onsubmit="return confirm('Supprimer TOUS les messages reçus ?');" style="margin: 0;">
            <button type="submit" class="btn-neon" style="padding:10px 20px; font-size:14px; background:rgba(255,0,0,0.2); border:none; color:#ff4444;">
                🗑️ Tout supprimer
            </button>
        </form>
    </div>

    <?php if (empty($messages)): ?>
        <p style="color:white; text-align:center;">Aucun message reçu.</p>
    <?php else: ?>
        <table class="message-table">
            <thead>
                <tr>
                    <th>📨 Sujet</th>
                    <th>👤 Expéditeur</th>
                    <th>📅 Reçu le</th>
                    <th>🗑️</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td style="text-align:left;">
                            <a href="view_received.php?id=<?= $msg['id'] ?>" style="color:<?= $msg['is_read'] ? '#aaa' : '#55ff88' ?>;">
                                <?= htmlspecialchars($msg['subject']) ?>
                                <?php if (!$msg['is_read']): ?>
                                    <span class="new-badge">• Non lu</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($msg['is_anonymous'] && !$msg['revealed']): ?>
                                <span style="color:#ff4444;">Anonyme</span>
                            <?php else: ?>
                                <a href="/ProjetPrison/views/profil.php?id=<?= $msg['sender_id'] ?>" style="color:#55ffff;">
                                    <?= htmlspecialchars($msg['sender_name']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
                        <td>
                            <form method="POST" action="../messages/delete_received.php" onsubmit="return confirm('Supprimer ce message ?');" style="margin: 0;">
                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                <button class="btn-neon" style="padding:4px 8px; font-size: 12px;">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
const box = document.getElementById('success-box');
if (box) {
    setTimeout(() => {
        box.style.opacity = '0';
        box.style.transition = 'opacity 1s ease';
    }, 3000);
}
</script>
