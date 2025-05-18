<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        if ($_POST['action'] === 'delete') {
            $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'action' => 'delete']);
            exit;
        }
        if ($_POST['action'] === 'treat') {
            $pdo->prepare("UPDATE comments SET reported = 0, validated_by_admin = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'action' => 'treat']);
            exit;
        }
    }

    // 🎯 Traitement tag
    if (isset($_POST['tag_comment_id'], $_POST['new_tag'])) {
        $tagId = (int) $_POST['tag_comment_id'];
        $newTag = trim($_POST['new_tag']);
        $stmt = $pdo->prepare("UPDATE comments SET tag = ? WHERE id = ?");
        $stmt->execute([$newTag, $tagId]);
        $_SESSION['flash_success'] = "Tag mis à jour avec succès.";
        header("Location: manage_comments.php");
        exit;
    }
}


$pageTitle = "Gestion des commentaires";

$flashSuccess = '';
if (!empty($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$onlyReported = isset($_GET['only_reported']);
$onlyAttachments = isset($_GET['only_attachments']);
$selectedTag = $_GET['tag'] ?? '';
$tagList = $pdo->query("SELECT DISTINCT tag FROM comments WHERE tag IS NOT NULL AND tag != '' ORDER BY tag ASC")->fetchAll(PDO::FETCH_COLUMN);

$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$where = ["(reported = 1 AND validated_by_admin = 0)"];


// Si l'admin coche "Signalés"
if ($onlyReported) $where[] = "reported = 1";

// Si l'admin coche "Avec pièce jointe"
if ($onlyAttachments) $where[] = "attachment IS NOT NULL AND attachment != ''";
if (!empty($selectedTag)) $where[] = "tag = " . $pdo->quote($selectedTag);


$whereSql = $where ? "WHERE " . implode(' AND ', $where) : '';

$totalCount = $pdo->query("SELECT COUNT(*) FROM comments $whereSql")->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

$stmt = $pdo->prepare("
    SELECT comments.*, posts.title AS post_title
    FROM comments
    JOIN posts ON comments.post_id = posts.id
    $whereSql
    ORDER BY reported DESC, created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

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
.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 0.8em;
}
.badge-reported {
    background: rgba(255,0,0,0.2);
    color: #ff5555;
}
.badge-attachment {
    background: rgba(0,255,255,0.2);
    color: #55ffff;
}

/* Uniformisation des boutons */
button, a.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 0.9em;
    color: white;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.3s, border 0.3s;
}
button:hover, a.btn-action:hover {
    background: rgba(255,165,0,0.2);
    border-color: rgba(255,165,0,0.5);
}

/* Inputs */
textarea, input[type="text"] {
    width: 100%;
    padding: 8px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: white;
    margin-top: 5px;
}

/* Formulaire de filtres */
#filter-form {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}
#filter-form label {
    color: white;
}
</style>

<div class="container">
    <h2 style="color:white;">💬 Gestion des commentaires</h2>

    <?php if ($flashSuccess): ?>
        <div class="comment-card" style="background: rgba(0,255,0,0.05); border-left: 3px solid #00ff99;">
            <p style="color: #00ff99;"><?= htmlspecialchars($flashSuccess) ?></p>
        </div>
    <?php endif; ?>

    <form id="filter-form" method="get">
    <label>
        <input type="checkbox" name="only_reported" <?= $onlyReported ? 'checked' : '' ?>> Signalés 🚩
    </label>
    <label>
        <input type="checkbox" name="only_attachments" <?= $onlyAttachments ? 'checked' : '' ?>> Avec pièce jointe 📎
    </label>
    <label>
        Tag :
        <select name="tag">
            <option value="">— Tous —</option>
            <?php foreach ($tagList as $tag): ?>
                <option value="<?= htmlspecialchars($tag) ?>" <?= $tag === $selectedTag ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tag) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">🎯 Filtrer</button>
</form>


    <?php if (empty($comments)): ?>
        <div class="comment-card" style="text-align:center;">
            <p style="color:white;">Aucun commentaire trouvé.</p>
        </div>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
    <div class="comment-card" id="comment-card-<?= $comment['id'] ?>">
        <div style="margin-bottom:10px;">
            <strong><?= htmlspecialchars($comment['author']) ?></strong> sur 
            <em style="color: #ffaa55;"><?= htmlspecialchars($comment['post_title']) ?></em>
            <?php if ($comment['reported']): ?>
                <span class="badge badge-reported">🚩 Signalé</span>
            <?php endif; ?>
            <?php if (!empty($comment['attachment'])): ?>
                <span class="badge badge-attachment">📎 Pièce jointe</span>
            <?php endif; ?>
        </div>

        <div style="margin-bottom:10px; color: #ccc;">
            <?= nl2br(htmlspecialchars($comment['content'])) ?>
        </div>

        <?php if (!empty($comment['attachment'])): ?>
            <p style="margin-top:10px;">
                📎 <a href="<?= htmlspecialchars($comment['attachment']) ?>" target="_blank" style="color:#55ffff;">Voir fichier</a>
            </p>
        <?php endif; ?>

        <div class="actions" style="margin-top:15px; flex-wrap: wrap;">
            <a href="edit_comment.php?id=<?= $comment['id'] ?>" class="btn-action">✏️ Modifier</a>
            <button class="btn-action delete-btn" data-id="<?= $comment['id'] ?>" style="background: rgba(255,0,0,0.2);">🗑 Supprimer</button>
            <?php if ($comment['reported'] && $comment['validated_by_admin'] == 0): ?>
    <button class="btn-action validate-btn" data-id="<?= $comment['id'] ?>">✅ Marquer traité</button>
<?php endif; ?>


        </div>

        <form method="post" action="manage_comments.php" style="margin-top: 15px;">
            <input type="hidden" name="tag_comment_id" value="<?= $comment['id'] ?>">
            <input type="text" name="new_tag" value="<?= htmlspecialchars($comment['tag'] ?? '') ?>" placeholder="🏷️ Ajouter un tag">
            <button type="submit" class="btn-action" style="margin-top:5px;">💾 Enregistrer tag</button>
        </form>

        <form method="post" style="margin-top: 15px;">
            <input type="hidden" name="reply_to" value="<?= $comment['id'] ?>">
            <input type="hidden" name="post_id" value="<?= $comment['post_id'] ?>">
            <textarea name="admin_reply_content" placeholder="Répondre en tant qu'admin..." required></textarea>
            <button type="submit" class="btn-action" style="margin-top:5px;">💬 Répondre</button>
        </form>

        <small style="color:#888; display:block; margin-top:10px;">Posté le <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></small>
    </div>
<?php endforeach; ?>


        <div style="text-align:center; margin-top: 30px;">
            <?php if ($page > 1): ?>
                <a href="manage_comments.php?page=<?= $page-1 ?><?= $onlyReported ? '&only_reported=1' : '' ?><?= $onlyAttachments ? '&only_attachments=1' : '' ?>" class="btn-action">⬅️ Précédent</a>
            <?php endif; ?>
            <span style="color:white; margin: 0 15px;">Page <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="manage_comments.php?page=<?= $page+1 ?><?= $onlyReported ? '&only_reported=1' : '' ?><?= $onlyAttachments ? '&only_attachments=1' : '' ?>" class="btn-action">Suivant ➡️</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    if (confirm("Supprimer ce commentaire ?")) {
      fetch('manage_comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${id}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const card = document.getElementById('comment-card-' + id);
          if (card) card.remove();
        }
      });
    }
  });
});

document.querySelectorAll('.validate-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const commentId = this.dataset.id;

    fetch('manage_comments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=treat&id=' + encodeURIComponent(commentId)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const card = document.getElementById('comment-card-' + commentId);
        if (card) card.remove();
      } else {
        alert('Erreur lors du traitement');
      }
    });
  });
});

</script>




<?php include '../includes/footer.php'; ?>
</body>
</html>