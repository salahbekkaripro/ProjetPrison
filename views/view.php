<?php
session_start();
require_once '../includes/db.php';          
require_once '../includes/functions.php';
require_once '../includes/header.php';
$message_id = $_GET['id'] ?? null;

if (!$message_id || !is_numeric($message_id)) {
    die("⛔ ID invalide.");
}

$stmt = $pdo->prepare("
    SELECT m.*, u.username AS sender_name 
    FROM private_messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.id = ? AND m.receiver_id = ?
");
$stmt->execute([$message_id, $_SESSION['user']['id']]);
$message = $stmt->fetch();

if (!$message) {
    die("⛔ Ce message n'existe pas ou ne vous appartient pas.");
}

// Marquer comme lu si nécessaire
if (!$message['is_read']) {
    $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE id = ?")->execute([$message_id]);
}

$customHeadStyle = <<<CSS

 /* Overlay modal */
  #confirm-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }
  #confirm-box {
    background: #222;
    border-radius: 12px;
    padding: 25px 30px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 0 15px #ff5555;
    text-align: center;
    color: #fff;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  #confirm-box h3 {
    margin-bottom: 15px;
    font-size: 1.5rem;
    color: #ff4444;
    text-shadow: 0 0 8px #ff4444;
  }
  #confirm-box p {
    margin-bottom: 25px;
    font-size: 1.1rem;
  }
  #confirm-box button {
    background: #ff5555;
    border: none;
    padding: 10px 25px;
    margin: 0 10px;
    border-radius: 6px;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s ease;
    box-shadow: 0 0 8px #ff4444;
  }
  #confirm-box button:hover {
    background: #ff2222;
  }
  #confirm-box button.cancel {
    background: #444;
    box-shadow: 0 0 5px #666;
  }
  #confirm-box button.cancel:hover {
    background: #666;
  }

CSS;
    
require_once '../includes/head.php';
include '../includes/navbar.php';    
?>


<div id="page-transition"></div>
<div id="app" class="glass-box" style="max-width: 800px; margin: auto; margin-top: 60px;">
    <h2 style="color:white;">
        ✉️ Message de 
        <a href="/ProjetPrison/views/profil.php?id=<?= $message['sender_id'] ?>" style="color: #ff5555;">
            <?= htmlspecialchars($message['sender_name']) ?>
        </a>
    </h2>

    <div style="color:#ff9999; font-weight:bold;">Sujet : <?= htmlspecialchars($message['subject']) ?></div>
    <div style="margin-top:10px; color:white;"><?= nl2br(htmlspecialchars($message['content'])) ?></div>
    <div style="margin-top:20px; color:grey; font-size:0.9em;">Reçu le : <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></div>

    <div style="margin-top: 20px;">
        <a href="send.php?reply_to=<?= $message['sender_id'] ?>" class="btn-neon">↩️ Répondre</a>
        <a href="inbox.php" class="btn-neon">⬅️ Retour à la boîte de réception</a>
        <button id="btnDelete" class="btn-neon" style="background:#ff4444; border:none;">🗑️ Supprimer</button>
    </div>
</div>

<!-- Modal confirmation -->
<div id="confirm-overlay">
  <div id="confirm-box">
    <h3>⚠️ Attention !</h3>
    <p>Êtes-vous sûr de vouloir supprimer ce message définitivement ? Cette action est irréversible.</p>
    <button id="confirmYes">Oui, supprimer</button>
    <button id="confirmNo" class="cancel">Annuler</button>
  </div>
</div>

<script>
  const btnDelete = document.getElementById('btnDelete');
  const overlay = document.getElementById('confirm-overlay');
  const confirmYes = document.getElementById('confirmYes');
  const confirmNo = document.getElementById('confirmNo');

  btnDelete.addEventListener('click', () => {
    overlay.style.display = 'flex';
  });

  confirmNo.addEventListener('click', () => {
    overlay.style.display = 'none';
  });

  confirmYes.addEventListener('click', () => {
    // Redirige vers le script de suppression
    window.location.href = '../ajax/delete_post.php?id=<?= $message['id'] ?>';
  });

  // Fermer modal si clic en dehors
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      overlay.style.display = 'none';
    }
  });

  // Optionnel : Fermer avec la touche ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.style.display === 'flex') {
      overlay.style.display = 'none';
    }
  });
</script>
