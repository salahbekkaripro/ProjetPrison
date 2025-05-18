<?php
session_start();
require_once '../includes/db.php';

require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifie que l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='text-align:center; color:red;'>Aucun topic sélectionné.</p>";
    require_once '../includes/footer.php';
    exit;
}

$post_id = (int) $_GET['id'];
// Critère de tri
$sort = $_GET['sort'] ?? 'oldest';


// Pagination
$commentsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $commentsPerPage;

// Récupère le topic
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND is_approved = 1");
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post) {
    echo "<p style='text-align:center; color:red;'>Topic introuvable ou non approuvé.</p>";
    require_once '../includes/footer.php';
    exit;
}

// Préparation des votes
$votes = [];
$votes_by_user = [];

$vote_stmt = $pdo->prepare("
    SELECT comment_id, 
           SUM(CASE WHEN type = 'like' THEN 1 WHEN type = 'dislike' THEN -1 ELSE 0 END) AS score 
    FROM likes WHERE comment_id IN (SELECT id FROM comments WHERE post_id = ?) 
    GROUP BY comment_id
");
$vote_stmt->execute([$post_id]);
foreach ($vote_stmt->fetchAll() as $row) {
    $votes[$row['comment_id']] = (int) $row['score'];
}

if (isset($_SESSION['user']['id'])) {
    $uid = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT comment_id, type FROM likes WHERE user_id = ? AND comment_id IN (SELECT id FROM comments WHERE post_id = ?)");
    $stmt->execute([$uid, $post_id]);
    foreach ($stmt->fetchAll() as $row) {
        $votes_by_user[$row['comment_id']] = $row['type'];
    }
}

// Récupération de tous les commentaires du post
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
  $all_stmt = $pdo->prepare("
SELECT c.*, u.username, u.avatar, u.role
      FROM comments c
      LEFT JOIN users u ON c.user_id = u.id
      WHERE c.post_id = ?
      ORDER BY c.created_at ASC
  ");
  $all_stmt->execute([$post_id]);
} else {
  $all_stmt = $pdo->prepare("
SELECT c.*, u.username, u.avatar, u.role
      FROM comments c
      LEFT JOIN users u ON c.user_id = u.id
      WHERE c.post_id = ? AND (c.reported = 0 OR c.validated_by_admin = 1)
      ORDER BY c.created_at ASC
  ");
  $all_stmt->execute([$post_id]);
}

$allComments = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

// Construction de l’arbre de commentaires
$commentMap = [];
// Préparer les citations automatiques
$commentsById = [];
foreach ($allComments as $c) {
    $commentsById[$c['id']] = $c;
}

foreach ($allComments as &$c) {
    $c['children'] = [];
    if (!empty($c['parent_id']) && isset($commentsById[$c['parent_id']])) {
      $parent = $commentsById[$c['parent_id']];
      $c['parent_author'] = $parent['username'] ?? $parent['author'] ?? 'anonyme';
      $excerpt = strip_tags($parent['content']);
      $c['parent_excerpt'] = mb_strimwidth($excerpt, 0, 100, '...');
  }
  
    $commentMap[$c['id']] = $c;
}
unset($c);

foreach ($commentMap as &$c) {
    if ($c['parent_id'] && isset($commentMap[$c['parent_id']])) {
        $commentMap[$c['parent_id']]['children'][] = &$c;
    }
}
unset($c);

// Pagination uniquement sur les commentaires parents
$topLevelComments = array_filter($commentMap, fn($c) => $c['parent_id'] === null);
// Tri des commentaires principaux
usort($topLevelComments, function($a, $b) use ($sort, $votes) {
  if ($sort === 'newest') {
      return strtotime($b['created_at']) - strtotime($a['created_at']);
  } elseif ($sort === 'popular') {
      $scoreA = $votes[$a['id']] ?? 0;
      $scoreB = $votes[$b['id']] ?? 0;
      return $scoreB - $scoreA;
  } else {
      return strtotime($a['created_at']) - strtotime($b['created_at']);
  }
});

$filterTag = $_GET['filter_tag'] ?? '';
if ($filterTag !== '') {
    $topLevelComments = array_filter($topLevelComments, fn($c) => $c['tag'] === $filterTag);
}

$totalComments = count($topLevelComments);
$totalPages = ceil($totalComments / $commentsPerPage);
$comments = array_slice(array_values($topLevelComments), $offset, $commentsPerPage);

// Fonction récursive d’affichage
function renderComments($comments, $level = 0) {
  global $votes, $votes_by_user;
  foreach ($comments as $comment) {
      $score = $votes[$comment['id']] ?? 0;
      $vote_type = $votes_by_user[$comment['id']] ?? null;
      $margin = 50 * $level;
      $class = $level > 0 ? 'reply-child' : '';


      echo '<div id="comment-' . $comment['id'] . '" class="comment-box ' . $class . '" style="margin-left:' . $margin . 'px;">';

    
      echo '<div style="display:flex; align-items:center; margin-bottom:6px;">';
      // [AVATAR] Affichage avec fallback si vide ou image manquante
      if (!empty($comment['avatar'])) {
        $avatarPath = 'uploads/avatars/' . basename($comment['avatar']);
    } else {
        $avatarPath = 'assets/img/default_avatar.jpg';
    }
    
    


    echo '<img src="' . htmlspecialchars($avatarPath) . '" alt="avatar" style="width:32px; height:32px; border-radius:50%; margin-right:10px;" onerror="this.onerror=null; this.src=\'assets/img/default_avatar.jpg\';">';

    
    
    if (!empty($comment['user_id'])) {
      $roleRaw = $comment['role'] ?? null;
      $role = strtolower($roleRaw ?: 'user');
      $label = strtoupper($roleRaw ?: 'membre');
      $badgeClass = 'role-badge role-badge-' . $role;
    
      echo '<strong><a href="profil.php?id=' . htmlspecialchars($comment['user_id']) . '" class="username-link">' . htmlspecialchars($comment['username']) . '</a></strong>';
      echo ' <span class="' . $badgeClass . '">' . $label . '</span>';
    } else {
      echo '<strong>' . htmlspecialchars($comment['author']) . '</strong>';
    }
    
        if (!empty($comment['tag'])) {
        // Couleurs par tag
        $tagColors = [
            'Aide' => ['#00bbff', '#003344'],
            'Discussion' => ['#ffaa00', '#332500'],
            'Question' => ['#44ff99', '#003322'],
            'Important' => ['#ff4444', '#330000']
        ];
        $tagColor = $tagColors[$comment['tag']] ?? ['#888', '#222'];
    
        echo '<span style="background:' . $tagColor[1] . '; color:' . $tagColor[0] . '; padding:4px 10px; font-size:0.75em; border-radius:20px; margin-left:10px;">🏷️ ' . htmlspecialchars($comment['tag']) . '</span>';
    }
    
    echo ' <button class="copy-link-btn" data-id="' . $comment['id'] . '" title="Copier le lien" style="background:none; border:none; color:#888; cursor:pointer; font-size:0.9em;">🔗</button>';
      echo '<span class="copy-confirm" id="copy-confirm-' . $comment['id'] . '" style="display:none; font-size:0.8em; color:#4fff6d; margin-left:5px;">Copié !</span>';
      echo '<span class="vote-score" style="margin-left:10px; font-weight:bold; color:#ff7a00;">▲ ' . $score . '</span>';

      // BOUTON SIGNALER
      if (isset($_SESSION['user']) && !$comment['reported']) {
          echo '<span id="report-box-' . $comment['id'] . '" style="margin-left:12px;">';
          echo '<a href="#" class="report-link" data-id="' . $comment['id'] . '" style="color:#ff5555; font-size:0.85em;">🚩 Signaler</a>';
          echo '</span>';
      } elseif ($comment['reported']) {
          echo '<span style="margin-left:12px; font-size:0.8em; color:#44ff88;">✅ Signalé</span>';
      }

      echo '<span style="margin-left:auto; font-size:0.85rem; color:#999;">' . date("d/m/Y H:i", strtotime($comment['created_at']));
      if (!empty($comment['updated_at']) && $comment['updated_at'] !== $comment['created_at']) {
          echo '<br><small style="color:#888;">Modifié le ' . date("d/m/Y H:i", strtotime($comment['updated_at'])) . '</small>';
      }
      echo '</span>';
      echo '</div>';

      // Bloc votes
      if (isset($_SESSION['user'])) {
          $likeClass = ($vote_type === 'like') ? 'active-vote' : '';
          $dislikeClass = ($vote_type === 'dislike') ? 'active-vote' : '';
          echo '<div class="vote-controls">';
          echo '<form method="post" action="#" style="display:inline; margin:0;">';
          echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
          echo '<input type="hidden" name="type" value="like">';
          echo '<button type="submit" class="vote-btn ' . $likeClass . '">▲</button>';
          echo '</form>';
          echo '<form method="post" action="#" style="display:inline; margin-left:4px;">';
          echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
          echo '<input type="hidden" name="type" value="dislike">';
          echo '<button type="submit" class="vote-btn ' . $dislikeClass . '">▼</button>';
          echo '</form>';
          echo '</div>';
      }

      // Contenu + pièce jointe + parent cité
      echo '<div id="comment-content-' . $comment['id'] . '" class="comment-content">';

      // Bloc parent_excerpt s’il existe
      // Bloc parent_excerpt affiché uniquement s’il n’est pas cité via >
if (!empty($comment['parent_excerpt']) && strpos($comment['content'], '>') !== 0) {
  echo '<div style="background: rgba(255,255,255,0.03); border-left: 4px solid #999; padding: 8px 12px; margin-bottom: 10px; border-radius: 8px; font-style: italic; color: #bbb;">';
  echo 'Réponse à : ' . htmlspecialchars($comment['parent_excerpt']);
  echo '</div>';
}

// Trouver l'ID du commentaire racine
$rootId = $comment['id'];
$current = $comment;
while (!empty($current['parent_id']) && isset($GLOBALS['commentMap'][$current['parent_id']])) {
    $current = $GLOBALS['commentMap'][$current['parent_id']];
    $rootId = $current['id'];
}

if ($rootId !== $comment['id']) {
    echo '<div style="text-align:right; margin-top:-5px; margin-bottom:8px;">';
    echo '<a href="#comment-' . $rootId . '" onclick="scrollToParent(' . $rootId . ')" style="color:#ffaa00; font-size:0.85em;">🔝 Voir message initial</a>';
    echo '</div>';
}



      // Contenu du commentaire + blockquote si `>` utilisé
      $raw = htmlspecialchars($comment['content']);
      $rendered = preg_replace('/^&gt;\s?(.*)$/m', '<blockquote>$1</blockquote>', $raw);
      echo nl2br($rendered);

      // Affichage pièce jointe
      if (!empty($comment['attachment'])) {
          $ext = pathinfo($comment['attachment'], PATHINFO_EXTENSION);
$filePath = '/ProjetPrison/uploads/comments/' . $comment['attachment'];
          echo '<div style="margin-top:10px;">';
          if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
              echo '<img src="' . $filePath . '" alt="image" style="max-width:100%; border-radius:10px; box-shadow:0 0 6px rgba(255,255,255,0.1);">';
          } else {
              echo '<a href="' . $filePath . '" target="_blank" style="color:#ff7a00; font-size:0.9em;">📎 Voir la pièce jointe</a>';
          }
          echo '</div>';
      }

      echo '</div>';

      // Bouton éditer
      if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    echo '<button class="edit-btn btn-neon" data-id="' . $comment['id'] . '">✏️ Modifier</button>';
    echo '<form method="post" class="edit-form" id="edit-form-' . $comment['id'] . '" style="display:none; margin-top:10px;">';
    echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
    echo '<textarea name="content" rows="3" style="width:100%; padding:8px; border-radius:8px;">' . htmlspecialchars($comment['content']) . '</textarea>';
    echo '<button type="submit" class="btn-neon" style="margin-top:8px;">💾 Enregistrer</button>';
    echo '</form>';
}


      // Bouton répondre
      echo '<button type="button" class="btn-reply-main" 
      data-id="' . $comment['id'] . '" 
      data-user="' . htmlspecialchars($comment['username'] ?? $comment['author']) . '" 
      data-content="' . htmlspecialchars(mb_strimwidth(strip_tags($comment['content']), 0, 80, '...')) . '"
      onclick="toggleReplyForm(' . $comment['id'] . ')">📝 Répondre</button>';
     if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
        echo '<button class="btn-delete-admin" data-id="' . $comment['id'] . '" title="Supprimer ce commentaire">🗑</button>';
    }
    
    
      echo '<div id="reply-form-' . $comment['id'] . '" style="display:none; margin-top:10px;">';
      echo '<form action="../ajax/submit_comment.php" method="post" enctype="multipart/form-data">';
      echo '<input type="hidden" name="post_id" value="' . $comment['post_id'] . '">';
      echo '<input type="hidden" name="parent_id" value="' . $comment['id'] . '">';
      echo '<textarea name="content" required placeholder="Votre réponse..." rows="6" style="width:100%; padding:12px; border-radius:10px; font-size:1rem; background:rgba(255,255,255,0.05); color:#fff; resize:vertical;"></textarea>';
      echo '<select name="tag" style="margin:10px 0; padding:8px; border-radius:8px; background:#222; color:white; border:1px solid #444;">';
      echo '<option value="">🏷️ Choisir un tag (optionnel)</option>';
echo '<option value="Discussion">💬 Discussion</option>';
echo '<option value="Aide">🆘 Aide</option>';
echo '<option value="Question">❓ Question</option>';
echo '<option value="Important">⚠️ Important</option>';
echo '</select>';

      echo '<input type="file" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt" style="margin:10px 0; background:rgba(255,255,255,0.03); color:white; border:none; padding:6px; border-radius:8px;"/>';
      echo '<button type="submit" class="btn-neon">Envoyer</button>';
      echo '</form></div>';

      echo '</div>'; // .comment-box

      if (!empty($comment['children'])) {
          renderComments($comment['children'], $level + 1);
      }
  }
}
 

require_once '../includes/head.php';
require_once '../includes/navbar.php';


?>

<div class="glass-box" style="max-width:900px; margin:40px auto;">
    <h2 class="text-2xl mb-4"><?= htmlspecialchars($post['title']) ?></h2>
    <div class="topic-meta" style="color:#aaa; margin-bottom:10px;">
        Par <strong><?= htmlspecialchars($post['author']) ?></strong> • <?= date("d/m/Y H:i", strtotime($post['created_at'])) ?>
    </div>
    <div class="topic-content" style="font-size:1.1rem; margin-bottom:30px;">
        <?= nl2br(htmlspecialchars($post['content'])) ?>
    </div>

    <hr style="border-color:rgba(255,255,255,0.1); margin:30px 0;">
    

<?php if (!empty($tags)): ?>
  <div style="margin-bottom: 20px; text-align: center;">
    <strong style="color:#aaa;">🏷️ Tags utilisés :</strong><br>
    <?php foreach ($tags as $t):
      // Couleur par tag
      $colors = [
        'Aide' => '#00bbff',
        'Discussion' => '#ffaa00',
        'Question' => '#44ff99',
        'Important' => '#ff4444'
      ];
      $color = $colors[$t] ?? '#888';
    ?>
      <a href="post.php?id=<?= $post_id ?>&filter_tag=<?= urlencode($t) ?>" 
         style="display: inline-block; margin: 5px 8px; padding: 6px 12px; font-size: 0.85em; 
         border-radius: 30px; background: <?= $color ?>22; color: <?= $color ?>; 
         border: 1px solid <?= $color ?>; text-decoration: none;">
         #<?= htmlspecialchars($t) ?>
      </a>
    <?php endforeach; ?>

    <?php if (!empty($_GET['filter_tag'])): ?>
      <a href="post.php?id=<?= $post_id ?>" 
         style="display: inline-block; margin: 5px 8px; padding: 6px 12px; font-size: 0.85em; 
         border-radius: 30px; background: rgba(255,255,255,0.05); color: #ccc; 
         border: 1px solid #999; text-decoration: none;">
         ✖️ Réinitialiser
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<h3 id="comments" class="text-xl mb-4">💬 Réponses</h3>
<div style="text-align:right; margin-bottom: 10px;">
  <form method="get" action="post.php">
    <input type="hidden" name="id" value="<?= $post_id ?>">
    <label for="sort" style="margin-right:5px; color:#aaa;">Trier par :</label>
    <select name="sort" id="sort" onchange="this.form.submit()" style="padding:4px; border-radius:6px;">
      <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus anciens</option>
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
      <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Les + populaires</option>
    </select>
    <input type="hidden" name="page" value="<?= $page ?>">
  </form>
</div>

<?php
$tags = array_unique(array_filter(array_column($allComments, 'tag')));
if (!empty($tags)): ?>
  <form method="get" style="text-align:right; margin-bottom: 15px;">
    <input type="hidden" name="id" value="<?= $post_id ?>">
    <label for="filter_tag" style="color:#aaa; margin-right:5px;">Filtrer par tag :</label>
    <select name="filter_tag" id="filter_tag" onchange="this.form.submit()" style="padding:6px; border-radius:6px; background:#222; color:#fff; border:1px solid #555;">
    <option value="">-- Tous --</option>
      <?php foreach ($tags as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= ($_GET['filter_tag'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= $sort ?>">
    <input type="hidden" name="page" value="<?= $page ?>">
  </form>
<?php endif; ?>

<?php if (empty($comments)) : ?>
    <p>Aucun commentaire pour le moment.</p>
<?php else : ?>
    <?php
        // ⚠️ CE BLOC DOIT ÊTRE EN PHP PUR
        $GLOBALS['commentMap'] = $commentMap;
        renderComments($comments);
    ?>
<?php endif; ?>


    <hr style="border-color:rgba(255,255,255,0.1); margin:30px 0;">
    <h3 class="text-xl mb-2">✏️ Ajouter une réponse</h3>
    <form class="ajax-reply-form" data-parent="0" enctype="multipart/form-data">
    <input type="hidden" data-name="post_id" value="<?= $post['id'] ?>">
    <textarea data-name="content" required placeholder="Votre réponse..." style="width:100%; padding:12px; border-radius:10px; background:rgba(255,255,255,0.05); color:#fff; margin-bottom:10px;"></textarea>
    <select data-name="tag" style="width:100%; padding:8px; margin-bottom:10px; border-radius:10px; background:#222; color:#fff; border:1px solid #444;">
 <option value="">🏷️ Choisir un tag (optionnel)</option>
  <option value="Discussion">💬 Discussion</option>
  <option value="Aide">🆘 Aide</option>
  <option value="Question">❓ Question</option>
  <option value="Important">⚠️ Important</option>
</select>



<input type="file" data-name="attachment" accept="image/*,.pdf,.doc,.docx" style="margin-bottom: 10px; color: white;">
    
    <button type="submit" style="
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fff;
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 0.95em;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(6px);
  box-shadow: 0 0 0 transparent;
  display: inline-block;
  margin-top: 10px;
" onmouseover="this.style.borderColor='#ff7a00'; this.style.color='#ff7a00'; this.style.boxShadow='0 0 10px rgba(255, 122, 0, 0.3)'; this.style.background='rgba(255,255,255,0.08)';"
onmouseout="this.style.borderColor='rgba(255,255,255,0.15)'; this.style.color='#fff'; this.style.boxShadow='0 0 0 transparent'; this.style.background='rgba(255,255,255,0.03)';">
📝 Répondre
</button>
    </form>

</div>

<!-- Pagination -->
<div style="text-align:center; margin-top:30px;">
  <?php if ($page > 1): ?>
    <a href="post.php?id=<?= $post_id ?>&page=<?= $page - 1 ?>&sort=<?= $sort ?>#comments" class="btn-warn-link">← Précédent</a>

<a href="post.php?id=<?= $post_id ?>&page=<?= $page + 1 ?>&sort=<?= $sort ?>#comments" class="btn-warn-link">Suivant →</a>

<a href="post.php?id=<?= $post_id ?>&page=<?= $page - 1 ?>&sort=<?= $sort ?>#comments" class="btn-warn-link">← Précédent</a>

<a href="post.php?id=<?= $post_id ?>&page=<?= $page + 1 ?>&sort=<?= $sort ?>#comments" class="btn-warn-link">Suivant →</a>

  <?php endif; ?>

  <span style="margin: 0 12px; color: #aaa;">Page <?= $page ?> sur <?= $totalPages ?></span>

  <?php if ($page < $totalPages): ?>
    <a href="post.php?id=<?= $post_id ?>&page=<?= $page + 1 ?>&sort=<?= $sort ?>#comments" class="btn-warn-link">Suivant →</a>
    <?php endif; ?>
</div>


<a href="home.php" class="btn-warn-link" style="position: fixed; top: 15px; left: 15px; z-index: 99;">← Accueil</a>

<script>
document.querySelectorAll('.vote-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();

    const form = btn.closest('form');
    const commentBox = btn.closest('.comment-box');
    const commentId = form.querySelector('[name="comment_id"]').value;
    const type = form.querySelector('[name="type"]').value;

    const clickedBtn = form.querySelector('.vote-btn');
    const wasAlreadyActive = clickedBtn.classList.contains('active-vote');

    fetch('../ajax/vote_comment.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `comment_id=${commentId}&type=${type}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Met à jour le score
        const scoreSpan = commentBox.querySelector('.vote-score');
        if (scoreSpan) scoreSpan.textContent = '▲ ' + data.score;

        // Réinitialise la surbrillance
        commentBox.querySelectorAll('.vote-btn').forEach(btn => btn.classList.remove('active-vote'));

        // Réactive si on ne retire pas
        if (!wasAlreadyActive) {
          clickedBtn.classList.add('active-vote');
        }
      }
    });
  });
});

function toggleReplyForm(id) {
  const form = document.getElementById('reply-form-' + id);
  const button = document.querySelector('.btn-reply-main[data-id="' + id + '"]');
  const textarea = form.querySelector('textarea');

  const parentComment = document.getElementById('comment-' + id);
  if (parentComment) {
    parentComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
    parentComment.style.boxShadow = '0 0 10px rgba(255,122,0,0.8)';
    setTimeout(() => {
      parentComment.style.boxShadow = 'none';
    }, 2000);
  }

  if (form.style.display === 'none') {
    form.style.display = 'block';

    const username = button.dataset.user;
    const snippet = button.dataset.content;
    const commentId = button.dataset.id;

    const citationHTML = `
      <div class="quote-preview">
        <blockquote>
          <a href="#comment-${commentId}" style="color:#ff7a00; text-decoration:none;">@${username}</a> : ${snippet}
        </blockquote>
      </div>
    `;

    // Supprime une citation déjà visible
    const existingPreview = form.querySelector('.quote-preview');
    if (existingPreview) existingPreview.remove();

    // Ajoute la citation
    textarea.insertAdjacentHTML('beforebegin', citationHTML);

    // Pré-remplir dans le champ
    textarea.value = `> @${username} : ${snippet}\n\n`;
    textarea.focus();
  } else {
    form.style.display = 'none';
  }
}


document.querySelectorAll('.report-link').forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    const commentId = this.dataset.id;

    fetch('../ajax/report_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(commentId)
    })
    .then(res => res.json())
    .then(data => {
      const box = document.getElementById('report-box-' + commentId);
      if (data.success) {
        box.innerHTML = '<span style="color:#44ff88; font-size: 0.9em;">✅ Signalé</span>';
      } else if (data.already_checked) {
        box.innerHTML = '<span style="color:#ffaa00; font-size: 0.9em;">⚠️ Ce message a déjà subi une vérification admin</span>';
      } else {
        box.innerHTML = '<span style="color:red;">❌ Erreur lors du signalement</span>';
      }
    });
  });
});



// Scroll fluide vers un commentaire ciblé dans l'URL
document.addEventListener('DOMContentLoaded', () => {
  const anchor = window.location.hash;
  if (anchor && anchor.startsWith("#comment-")) {
    const target = document.querySelector(anchor);
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.style.boxShadow = '0 0 10px rgba(255,122,0,0.8)';
      setTimeout(() => {
        target.style.boxShadow = 'none';
      }, 2000);
    }
  }
});

document.querySelectorAll('.copy-link-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const commentId = btn.dataset.id;
    const url = `${window.location.origin}${window.location.pathname}?id=<?= $post_id ?>#comment-${commentId}`;

    navigator.clipboard.writeText(url).then(() => {
      const confirm = document.getElementById('copy-confirm-' + commentId);
      confirm.style.display = 'inline';
      setTimeout(() => { confirm.style.display = 'none'; }, 2000);
    });
  });
});

document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    const form = document.getElementById('edit-form-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
  });
});

document.querySelectorAll('.edit-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const commentId = form.querySelector('[name="comment_id"]').value;
    const content = form.querySelector('[name="content"]').value;

    fetch('../ajax/edit_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `comment_id=${commentId}&content=${encodeURIComponent(content)}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const commentBox = document.getElementById('comment-content-' + commentId);
        commentBox.innerHTML = content.replace(/\n/g, '<br>');
        form.style.display = 'none';
      } else {
        alert("Erreur : " + (data.error || "Modification échouée"));
      }
    })
    .catch(err => {
      alert("Erreur AJAX : " + err);
    });
  });
});


document.querySelectorAll('.btn-delete-admin').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id;
    if (!confirm("Supprimer ce commentaire ?")) return;

    fetch('../ajax/delete_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const commentBox = document.getElementById('comment-' + id);
        if (commentBox) {
          commentBox.style.transition = 'opacity 0.4s ease';
          commentBox.style.opacity = '0';
          setTimeout(() => commentBox.remove(), 400);
        }
      } else {
        alert("Erreur : " + (data.error || "Suppression impossible"));
      }
    });
  });
});

function scrollToParent(id) {
  const parent = document.getElementById('comment-' + id);
  if (parent) {
    const top = parent.getBoundingClientRect().top + window.pageYOffset - 100;
    window.scrollTo({
      top: top,
      behavior: 'smooth'
    });

    parent.style.transition = 'box-shadow 0.4s ease';
    parent.style.boxShadow = '0 0 10px rgba(255,122,0,0.8)';
    setTimeout(() => {
      parent.style.boxShadow = 'none';
    }, 2000);
  }
}

document.querySelectorAll('.ajax-reply-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const parentId = this.dataset.parent;
    const formData = new FormData();

    this.querySelectorAll('[data-name]').forEach(el => {
      if (el.type === 'file') {
        if (el.files[0]) formData.append(el.dataset.name, el.files[0]);
      } else {
        formData.append(el.dataset.name, el.value);
      }
    });

    formData.append('parent_id', parentId);

    fetch('../ajax/submit_comment.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload(); // tu peux remplacer par insertion dynamique plus tard
      } else {
        alert("Erreur : " + data.error);
      }
    });
  });
});


</script>
<?php require_once '../includes/footer.php'; ?>