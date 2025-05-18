<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';


$isVisitorMode = isset($_GET['id']) && is_numeric($_GET['id']);
$targetUserId = $isVisitorMode ? (int) $_GET['id'] : $_SESSION['user']['id'];

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$targetUserId]);
$targetUser = $stmtUser->fetch();

if (!$targetUser) {
    die("<p style='color:white; text-align:center;'>⛔ Utilisateur introuvable.</p>");
}

$pageTitle = $isVisitorMode ? "Profil de " . htmlspecialchars($targetUser['username']) : "Mon profil";

$stmt = $pdo->prepare("
    SELECT comments.*, posts.title AS post_title 
    FROM comments 
    JOIN posts ON comments.post_id = posts.id 
WHERE comments.user_id = ? AND (comments.reported = 0 OR comments.validated_by_admin = 1)
    ORDER BY comments.created_at DESC
");
$stmt->execute([$targetUserId]);
$comments = $stmt->fetchAll();
$commentsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalComments = count($comments);
$totalPages = ceil($totalComments / $commentsPerPage);
$offset = ($page - 1) * $commentsPerPage;

$paginatedComments = array_slice($comments, $offset, $commentsPerPage);

$customHeadStyle = <<<CSS
container {
    max-width: 1000px;
    margin: 50px auto;
}
.section-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    color: white;
}
.section-box h2, .section-box h3 {
    color: #ffa500;
    margin-bottom: 15px;
}
.avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-top: 10px;
}
.comment-list li {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
    color: white;
}
button.btn-neon, a.btn-neon {
    margin-top: 10px;
}
#bio-counter {
    text-align: right;
    font-size: 0.85em;
    color: #ccc;
    margin-top: 5px;
}
.comment-history {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.comment-block {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 165, 0, 0.1);
    border-radius: 12px;
    padding: 15px 20px;
    backdrop-filter: blur(6px);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.02);
    transition: all 0.3s ease;
}

.comment-block:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 165, 0, 0.2);
    box-shadow: 0 0 15px rgba(255, 165, 0, 0.1);
}

.comment-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.comment-title {
    color: #ffaa00;
    font-weight: bold;
    text-decoration: none;
}

.comment-title:hover {
    text-decoration: underline;
    color: #ffc94d;
}

.comment-date {
    font-size: 0.85em;
    color: #ccc;
}

.comment-content {
    font-size: 1rem;
    color: #eee;
    line-height: 1.5;
}



CSS;
    
    
    

?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<body>
<?php include '../includes/navbar.php'; ?>
<div id="page-transition"></div>

<div class="container">


<?php if (!empty($_SESSION['flash_success'])): ?>
    <div style="color:lime; margin-bottom:20px; font-weight:bold; text-align:center;">
        ✅ <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

    <div class="section-box">
        <h2><?= $isVisitorMode ? "👤 Profil de " . htmlspecialchars($targetUser['username']) : "👤 Mes informations" ?></h2>
        <?php
  $roleRaw = $targetUser['role'] ?? null;
  $role = strtolower($roleRaw ?: 'user');
  $label = strtoupper($roleRaw ?: 'membre');
  $roleClass = 'role-badge-' . $role;
?>
<div class="role-badge-container" style="margin-top:10px;">
  <span class="role-badge <?= $roleClass ?>"><?= $label ?></span>
</div>




        <?php if (!$isVisitorMode): ?>
            <p><strong>Email :</strong> <?= htmlspecialchars($_SESSION['user']['email']) ?></p>
        <?php endif; ?>

        <?php
$avatar = !empty($targetUser['avatar'])
    ? '/ProjetPrison/uploads/avatars/' . basename($targetUser['avatar'])
    : '/ProjetPrison/assets/img/default_avatar.jpg';
?>
<img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="avatar" loading="lazy"
onerror="this.onerror=null; this.src='assets/img/default_avatar.jpg';"><br>

<?php if (!$isVisitorMode && !empty($targetUser['avatar'])): ?>
    <form method="post" action="../ajax/delete_avatar.php" onsubmit="return confirm('Supprimer votre photo de profil ?')" style="margin-top:10px;">
        <button type="submit" class="btn-neon" style="background:#400; color:#fff;">❌ Supprimer ma photo</button>
    </form>
<?php endif; ?>


        <?php if ($isVisitorMode && !empty($targetUser['bio'])): ?>
            <div style="margin-top: 15px;">
                <h3>🧾 Présentation</h3>
                <p><?= nl2br(htmlspecialchars($targetUser['bio'])) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($isVisitorMode && isset($_SESSION['user']['id'])): ?>
    <a href="send.php?id=<?= $targetUser['id'] ?>&from_profile=<?= $targetUser['id'] ?>" class="btn-neon" style="display:inline-block; margin-top:15px;">
        ✉️ Envoyer un message
    </a>
<?php endif; ?>

    </div>

    <?php if (!$isVisitorMode): ?>
        <div class="section-box">
            <h3>🖼️ Changer mon avatar</h3>
            <form method="post" action="../ajax/upload_avatar.php" enctype="multipart/form-data" class="custom-file-upload">
                <label for="file-upload">📂 Choisir un fichier</label><br>
                <input id="file-upload" type="file" name="avatar" accept="image/png, image/jpeg" required>
                <span id="file-name">Aucun fichier choisi</span><br><br>
                <button type="submit" class="btn-neon">Uploader</button>
            </form>
        </div>

        <div class="section-box">
            <h3>📝 Ma bio</h3>
            <form method="post" action="../ajax/update_bio.php">
                <textarea name="bio" id="bio-input" rows="4" maxlength="500" placeholder="Décris-toi en quelques lignes..." style="width:100%;"><?= htmlspecialchars($targetUser['bio'] ?? '') ?></textarea>
                <p id="bio-counter">0 / 500</p>
                <button type="submit" class="btn-neon">Mettre à jour</button>
            </form>
        </div>

        <div class="section-box">
            <h3>🔑 Changer mon mot de passe</h3>
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <p style="color:green;">✅ <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></p>
            <?php elseif (!empty($_SESSION['flash_error'])): ?>
                <p style="color:red;">❌ <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></p>
            <?php endif; ?>

            <button onclick="togglePasswordForm()" class="btn-neon">Modifier mon mot de passe</button>
            <div id="password-form" style="display: none; margin-top: 10px;">
                <form method="post" action="../ajax/change_password.php">
                    <input type="password" name="current_password" placeholder="Mot de passe actuel" required><br><br>
                    <input type="password" name="new_password" placeholder="Nouveau mot de passe" required><br><br>
                    <input type="password" name="confirm_password" placeholder="Confirmer le nouveau mot de passe" required><br><br>
                    <button type="submit" class="btn-neon">Valider</button>
                </form>
            </div>
        </div>

        <div class="section-box">
            <h3>❌ Supprimer mon compte</h3>
            <button onclick="confirmDelete()" class="btn-neon" style="background:#400; color:#ff1919;">Supprimer définitivement</button>
            <form id="delete-form" method="post" action="../ajax/delete_account.php" style="display:none;">
                <input type="hidden" name="confirm_delete" value="1">
            </form>
            <div id="delete-overlay">Suppression du compte...</div>
        </div>
    <?php endif; ?>

    <div class="section-box">
        <h3>💬 Commentaires postés</h3>
        <?php if (empty($comments)) : ?>
            <p><?= $isVisitorMode ? "Cet utilisateur n'a encore posté aucun commentaire." : "Vous n'avez encore posté aucun commentaire." ?></p>
        <?php else : ?>
            <div class="comment-history">
            <?php foreach ($paginatedComments as $comment): ?>
                <div class="comment-block">
            <div class="comment-meta">
            <a href="post.php?id=<?= $comment['post_id'] ?>#comment-<?= $comment['id'] ?>" class="comment-title">
            🔗 Sujet : <?= htmlspecialchars($comment['post_title']) ?>
                </a>
                <?php if (!empty($comment['tag'])): 
    $colors = [
        'Aide' => '#00bbff',
        'Discussion' => '#ffaa00',
        'Question' => '#44ff99',
        'Important' => '#ff4444'
    ];
    $color = $colors[$comment['tag']] ?? '#ccc';
?>
<span style="margin-left:10px; padding:3px 10px; font-size:0.75em; border-radius:20px; background:<?= $color ?>22; color:<?= $color ?>;">
    🏷️ <?= htmlspecialchars($comment['tag']) ?>
</span>
<?php endif; ?>

                <span class="comment-date">🕓 <?= date("d/m/Y H:i", strtotime($comment['created_at'])) ?></span>
            </div>
            <div class="comment-content">
                <?= nl2br(htmlspecialchars($comment['content'])) ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div style="text-align: center; margin-top: 20px;">
  <?php if ($page > 1): ?>
    <a href="profil.php?id=<?= $targetUserId ?>&page=<?= $page - 1 ?>" class="btn-warn-link">← Précédent</a>
  <?php endif; ?>

  <span style="margin: 0 12px; color: #aaa;">Page <?= $page ?> sur <?= $totalPages ?></span>

  <?php if ($page < $totalPages): ?>
    <a href="profil.php?id=<?= $targetUserId ?>&page=<?= $page + 1 ?>" class="btn-warn-link">Suivant →</a>
  <?php endif; ?>
</div>
</div>

        <?php endif; ?>
    </div>
</div>

<script>
function togglePasswordForm() {
    var form = document.getElementById('password-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
function confirmDelete() {
    if (confirm('Voulez-vous vraiment supprimer votre compte ?')) {
        document.getElementById('delete-form').submit();
    }
}
const fileUpload = document.getElementById('file-upload');
const fileName = document.getElementById('file-name');
if (fileUpload) {
    fileUpload.addEventListener('change', function() {
        fileName.textContent = this.files[0]?.name || 'Aucun fichier choisi';
    });
}
const bioInput = document.getElementById('bio-input');
const bioCounter = document.getElementById('bio-counter');
if (bioInput) {
    bioInput.addEventListener('input', function() {
        bioCounter.textContent = `${this.value.length} / 500`;
    });
    bioCounter.textContent = `${bioInput.value.length} / 500`;
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>