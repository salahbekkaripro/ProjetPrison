<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='glass-box' style='color:#ff4444; margin:50px auto; max-width:600px; padding:30px; text-align:center;'>
            🚫 Message introuvable ou déjà détruit.<br><br>
            <a href='inbox.php' class='btn-neon'>🔙 Retour à la boîte de réception</a>
          </div>";
    exit;
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT m.*, u.username AS sender_name
    FROM private_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.id = ? AND m.receiver_id = ?
");
$stmt->execute([$id, $_SESSION['user']['id']]);
$message = $stmt->fetch();

if (!$message) {
    echo "<div class='glass-box' style='color:#ff4444; margin:50px auto; max-width:600px; padding:30px; text-align:center;'>
            🚫 Message introuvable ou déjà détruit.<br><br>
            <a href='inbox.php' class='btn-neon'>🔙 Retour à la boîte de réception</a>
          </div>";
    exit;
}

// Marquer comme lu
$update = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE id = ?");
$update->execute([$id]);
?>

<style>
@keyframes dust {
    0% { opacity: 1; filter: none; transform: none; }
    20% { filter: blur(1px) brightness(1.2); }
    50% { transform: scale(1.05) rotate(1deg); filter: grayscale(0.5); }
    100% { opacity: 0; transform: translateY(-30px) rotateZ(20deg); filter: blur(3px) grayscale(1); }
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
@keyframes redflash {
    0% { background-color: transparent; }
    100% { background-color: #440000; }
}
#dust-effect {
    animation: dust 2.5s forwards ease-in;
}
#countdown {
    color: #ff4444;
    font-weight: bold;
    font-size: 30px;
    margin-top: 15px;
    text-align: center;
}
#timer {
    font-size: 40px;
    color: #00ff00;
    transition: all 0.3s ease;
}
#timer.warning {
    color: #ff0000;
    animation: blink 1s infinite;
}
#red-flash {
    animation: redflash 2.5s forwards;
}
</style>

<?php require_once '../includes/navbar.php'; ?>

<div id="page-transition"></div>

<div id="app" class="glass-box" style="max-width: 800px; margin: auto; margin-top: 60px;">
    <h2 style="color:white;">📥 Message reçu</h2>

    <?php if ($message['self_destruct']): ?>
        <div id="countdown">
            Autodestruction dans : <span id="timer">7</span> secondes
        </div>
    <?php endif; ?>

    <p><strong style="color:#ff9900;">Sujet :</strong> <?= htmlspecialchars($message['subject']) ?></p>
    <p><strong style="color:#ff9900;">Expéditeur :</strong>
    <?php if ($message['is_anonymous'] && !$message['revealed']): ?>
        <span style="color:#ff4444;">Anonyme</span>
    <?php else: ?>
        <a href="profil.php?id=<?= $message['sender_id'] ?>" style="color:#55ffff;">
            <?= htmlspecialchars($message['sender_name']) ?>
        </a>
    <?php endif; ?>
    </p>

    <p><strong style="color:#ff9900;">Reçu le :</strong> <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></p>

    <hr style="border-color:#ff5a5a;">

    <div style="background-color: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; color:white;">
        <?= nl2br(htmlspecialchars($message['content'])) ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="inbox.php" class="btn-neon">💙 Retour à la boîte de réception</a>
    </div>
</div>

<?php if ($message['self_destruct']): ?>
<script>
(function(){
    let countdownInterval;
    let destructionTimeout;
    let seconds = 7;
    let cancelled = false;

    function triggerDestruction() {
        if (cancelled) return;
        clearInterval(countdownInterval);
        clearTimeout(destructionTimeout);

        const app = document.getElementById('app');
        const body = document.body;

        if (app) app.id = 'dust-effect';
        if (body) body.id = 'red-flash';

        // Supprimer le message via POST (comme la suppression manuelle)
        setTimeout(() => {
            fetch('../messages/delete_received.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'message_id=<?= $id ?>'
            });
        }, 1000);

        // Redirection après l’effet visuel
        setTimeout(() => {
            if (!cancelled) window.location.href = 'inbox.php';
        }, 2500);
    }

    function startDestruction() {
        const timer = document.getElementById('timer');

        countdownInterval = setInterval(() => {
            seconds--;
            if (timer) {
                timer.textContent = seconds;
                if (seconds <= 3) {
                    timer.classList.add('warning');
                }
            }
            if (seconds <= 0) {
                triggerDestruction();
            }
        }, 1000);

        destructionTimeout = setTimeout(triggerDestruction, (seconds + 1) * 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startDestruction);
    } else {
        startDestruction();
    }

    const observer = new MutationObserver(() => {
        if (!document.getElementById('timer')) {
            cancelled = true;
            clearInterval(countdownInterval);
            clearTimeout(destructionTimeout);
        }
    });

    observer.observe(document.getElementById('app'), {
        childList: true,
        subtree: true
    });

    window.addEventListener("beforeunload", () => {
        cancelled = true;
        clearInterval(countdownInterval);
        clearTimeout(destructionTimeout);
    });

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") {
            cancelled = true;
            clearInterval(countdownInterval);
            clearTimeout(destructionTimeout);
        }
    });
})();
</script>
<?php endif; ?>
