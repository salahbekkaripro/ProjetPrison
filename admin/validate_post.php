<?php
session_start();
require_once '../includes/functions.php';
require_admin_login();
require_once '../includes/db.php';
require_once '../includes/head.php';
include '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div id="page-transition"></div>

<div class="container" style="max-width: 1200px; margin: auto; margin-top: 60px;">

    <h2 style="color:white; font-size:2rem; margin-bottom: 30px;">📝 Sujets en attente de validation</h2>

    <?php
    $stmt = $pdo->query("SELECT * FROM posts WHERE is_approved = 0 ORDER BY created_at DESC");
    $posts = $stmt->fetchAll();
    ?>

    <?php if (empty($posts)): ?>
        <div class="comment-card" style="text-align:center;">
            <p style="color:white;">Aucun sujet en attente pour le moment.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="comment-card" id="post-<?= $post['id'] ?>">
                <div style="margin-bottom: 10px;">
                    <strong style="font-size: 1.3rem; color: #ffaa55;"><?= htmlspecialchars($post['title']) ?></strong>
                </div>

                <div style="color:#ccc; margin-bottom:10px;">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>

                <div style="margin-bottom: 10px; font-size: 0.9em; color: #888;">
                    🧑 Auteur : <span style="color: #aaa;"><?= htmlspecialchars($post['author']) ?></span> — 🕓 <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                </div>

                <div class="actions">
                    <button class="btn-neon validate-btn" data-id="<?= $post['id'] ?>">✅ Valider</button>
                    <button class="btn-neon delete-btn" data-id="<?= $post['id'] ?>" style="background: rgba(255,0,0,0.2);">❌ Supprimer</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- ✅ Conteneur pour les toasts -->
<div id="toast-container"></div>

<style>
body { background: #0d0d0d; }
.container { max-width: 1200px; margin: 50px auto; }
.comment-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
}
.actions {
    margin-top: 15px;
    display: flex;
    gap: 15px;
}
.actions button {
    padding: 8px 16px;
    border: none;
    border-radius: 10px;
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 0.95rem;
    cursor: pointer;
}
.actions button:hover {
    background: rgba(255,165,0,0.2);
}
.card-swipe-left {
  animation: swipeLeft 0.6s forwards ease-out;
}
.card-swipe-right {
  animation: swipeRight 0.6s forwards ease-out;
}
@keyframes swipeLeft {
  0% { transform: translateX(0); opacity: 1; }
  100% { transform: translateX(-150%) rotate(-5deg); opacity: 0; }
}
@keyframes swipeRight {
  0% { transform: translateX(0); opacity: 1; }
  100% { transform: translateX(150%) rotate(5deg); opacity: 0; }
}

/* 🟠 TOAST STYLES */
#toast-container {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 9999;
}
.toast {
  background: rgba(48, 47, 44, 0.2);
  color: #fff;
  border: 2px solid transparent;
  padding: 16px 28px;
  margin-top: 14px;
  border-radius: 12px;
  backdrop-filter: blur(8px);
  font-size: 1.1rem;
  line-height: 1.4;
  font-weight: 500;
  box-shadow: 0 0 15px rgba(147, 147, 147, 0.3);
  animation: fadeInOut 4s ease forwards;
}


.toast.success {
  border-left-color:rgb(112, 252, 147);
}
.toast.error {
  border-left-color:rgb(255, 129, 129);
}
@keyframes fadeInOut {
  0% { opacity: 0; transform: translateY(20px); }
  10% { opacity: 1; transform: translateY(0); }
  90% { opacity: 1; }
  100% { opacity: 0; transform: translateY(-10px); }
}
</style>

<script src="/ProjetPrison/assets/js/validate-post.js"></script>
<script>
  // 🔁 Si on est arrivé par rechargement classique (pas AJAX), on initialise manuellement
  if (typeof initValidatePostPage === "function") {
    console.log("🟢 Fallback: appel initValidatePostPage depuis validate_post.php");
    initValidatePostPage();
  }
</script>

<?php include '../includes/footer.php'; ?>