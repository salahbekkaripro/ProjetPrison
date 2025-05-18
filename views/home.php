<?php
session_start();
$pageTitle = "Discussions";
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Pagination
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'activity';

$params = [];

$searchSql = '';
if (!empty($search)) {
    $searchSql = "AND (p.title LIKE :search OR p.content LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql = "SELECT p.*, u.role, u.username,
        GREATEST(
            UNIX_TIMESTAMP(p.created_at), 
            IFNULL((SELECT UNIX_TIMESTAMP(MAX(c.created_at)) FROM comments c WHERE c.post_id = p.id), 0)
        ) AS last_activity,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS replies,
        (SELECT author FROM comments c2 WHERE c2.post_id = p.id ORDER BY c2.created_at DESC LIMIT 1) AS last_author,
        (SELECT created_at FROM comments c2 WHERE c2.post_id = p.id ORDER BY c2.created_at DESC LIMIT 1) AS last_date
        FROM posts p
LEFT JOIN users u ON u.username = p.author

        WHERE p.is_approved = 1
        $searchSql
        ORDER BY " . match ($sort) {
            'popular' => 'replies DESC',
            'newest' => 'p.created_at DESC',
            'oldest' => 'p.created_at ASC',
            default => 'last_activity DESC'
        } . "
        LIMIT :limit OFFSET :offset";


$stmt = $pdo->prepare($sql);
$params[':limit'] = $perPage;
$params[':offset'] = $offset;

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$posts = $stmt->fetchAll();

// Total posts (for pagination)
if (!empty($search)) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE is_approved = 1 AND (title LIKE :search OR content LIKE :search)");
    $countStmt->execute([':search' => '%' . $search . '%']);
    $totalPosts = $countStmt->fetchColumn();
} else {
    $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts WHERE is_approved = 1")->fetchColumn();
}
$totalPages = ceil($totalPosts / $perPage);

$customHeadStyle = <<<CSS

.container { max-width: 1200px; margin: 50px auto; }
.search-box, .discussion-card, .pagination-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}
input[type="text"] {
    width: 100%;
    padding: 12px;
    background: rgba(255,255,255,0.05);
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 16px;
}
.btn-neon {
    background: rgba(255,255,255,0.1);
    padding: 10px 20px;
    border-radius: 10px;
    color: white;
    border: none;
    font-size: 0.95em;
    cursor: pointer;
    transition: background 0.3s;
}
.btn-neon:hover {
    background: rgba(255,165,0,0.3);
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
    <h2 style="color: white; margin-bottom: 30px;">📚 Discussions disponibles</h2>

    <form method="get" style="text-align: right; margin-bottom: 20px;">
    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
    <label for="sort" style="color:#aaa;">Trier par :</label>
    <select name="sort" id="sort" onchange="this.form.submit()" style="margin-left: 8px; padding: 4px; border-radius: 6px;">
        <option value="activity" <?= $sort === 'activity' ? 'selected' : '' ?>>Dernière activité</option>
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus anciens</option>
        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Les + populaires</option>
    </select>
</form>


    <form method="get" class="search-box">
        <input type="text" name="search" placeholder="🔎 Rechercher un sujet..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" style="padding: 10px 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,165,0,0.2); border-radius: 10px; color: white; font-weight: bold; font-size: 0.95em; cursor: pointer; margin-top:10px;">🔎 Rechercher</button>
        </form>

    <?php if (empty($posts)): ?>
        <div class="discussion-card" style="text-align: center;">
            <p style="color:white;">Aucune discussion pour le moment.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="discussion-card">
                <h3 style="color: #ff5555; margin-bottom: 8px;">
                    <a href="post.php?id=<?= $post['id'] ?>" style="color: #ff5555; text-decoration: none;">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h3>
                <p style="color: #bbb; margin-bottom: 5px;">
  par <?= htmlspecialchars($post['author']) ?>
  <?php
  $roleRaw = $post['role'] ?? null;
  $role = strtolower($roleRaw ?: 'user');
  $label = strtoupper($roleRaw ?: 'membre');
?>
<span class="role-badge role-badge-<?= $role ?>"><?= $label ?></span>

  — <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
</p>
                <div style="display: flex; justify-content: space-between; color: #ccc; margin-top: 10px;">
                    <div>💬 <?= $post['replies'] ?> réponse(s)</div>
                    <div>🕓 <?= $post['last_author'] ? htmlspecialchars($post['last_author']) . ' - ' . date('d/m/Y H:i', strtotime($post['last_date'])) : 'Aucune réponse' ?></div>
                </div>
            </div> <!-- fin du bloc post-item -->
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <div class="pagination-box" style="text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>" class="btn-neon">⬅️ Précédent</a>
            <?php endif; ?>

            <span style="color:white; margin: 0 15px;">Page <?= $page ?> / <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>" class="btn-neon">Suivant ➡️</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div> <!-- fin de .container -->

<?php include '../includes/footer.php'; ?>
</body>
</html>
