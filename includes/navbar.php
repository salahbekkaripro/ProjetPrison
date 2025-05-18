<?php
if (!isset($pdo)) {
    require_once '../includes/db.php';
}

$notifCount = 0;
$pendingPostsCount = 0;
$pendingSanctionsCount = 0;

$isAdmin = false;
$isGestionnaire = false;
$isPrisonnier = false;

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    $role = $_SESSION['user']['role'] ?? '';

    $isAdmin = ($role === 'admin' || $role === 'gardien');
    $isGestionnaire = $role === 'gestionnaire';
    $isPrisonnier = $role === 'prisonnier';

    // 🔎 Sanctions en attente (gestionnaire)
    if ($isGestionnaire) {
        $stmtSanctions = $pdo->query("
            SELECT COUNT(*) 
            FROM infraction i 
            LEFT JOIN sanction s ON s.infraction_id = i.id 
            WHERE s.id IS NULL
        ");
        $pendingSanctionsCount = (int) $stmtSanctions->fetchColumn();
    }

    // Admin: commentaires signalés et posts à valider
    if ($isAdmin) {
        $stmtNotif = $pdo->query("SELECT COUNT(*) FROM comments WHERE reported = 1 AND validated_by_admin = 0");
        $notifCount = (int) $stmtNotif->fetchColumn();

        $stmtPosts = $pdo->query("SELECT COUNT(*) FROM posts WHERE is_approved = 0");
        $pendingPostsCount = (int) $stmtPosts->fetchColumn();
    }
}

// Dernier post approuvé
$latestPostId = null;
$stmt = $pdo->query("SELECT id FROM posts WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 1");
$latestPostId = $stmt->fetchColumn();
?>

<nav class="navbar-box">
    <div class="nav-top">
        <a href="/ProjetPrison/views/home.php">🏠 Forum</a>
        <?php if ($latestPostId): ?>
            | <a href="/ProjetPrison/views/post.php?id=<?= $latestPostId ?>">🆕 Dernier topic</a>
        <?php endif; ?>

        <?php if ($isGestionnaire): ?>
            | <a href="/ProjetPrison/views/gestionnaire/gestion_stock.php">📦 Gestion stock</a>
            | <a href="/ProjetPrison/views/prisonnier/rapport_journalier.php">📋 Rapports</a>
            | <a href="/ProjetPrison/views/gestionnaire/gestion_sanctions.php">⚖️ Sanctions
                <?php if ($pendingSanctionsCount > 0): ?>
                    <span class="notif-red"><?= $pendingSanctionsCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if ($isPrisonnier): ?>
            | <a href="/ProjetPrison/views/prisonnier/prisonnier_dashboard.php">🏠 Tableau de bord</a>
            | <a href="/ProjetPrison/views/prisonnier/acheter_objet.php">🛒 Boutique</a>
            | <a href="/ProjetPrison/views/prisonnier/infractions_prisonnier.php">📄 Mes infractions</a>
            | <a href="/ProjetPrison/views/prisonnier/travail_prisonnier.php">🧱 Travail</a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user'])): ?>
            | <a href="/ProjetPrison/views/inbox.php">
                📬 Messages
                <span id="msg-count" class="notif-red" style="display:none;"></span>
            </a>
            | <a href="/ProjetPrison/views/notifications.php">
                🔔 Réponses
                <span id="notif-count" class="notif-red" style="display:none;"></span>
            </a>
                    <a href="/ProjetPrison/views/mon_planning.php">📅 Mon planning</a>

            | <a href="/ProjetPrison/views/profil.php">👤 Profil</a>
            | <a href="/ProjetPrison/ajax/logout.php" >🚪 Déconnexion</a>
        <?php else: ?>
            | <a href="/ProjetPrison/views/login.php">Connexion</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['user'])): ?>
        <div class="nav-bottom">
            <?php if ($isAdmin): ?>
                | <a href="/ProjetPrison/views/admin/dashboard.php">🏠 Tableau de bord</a>
                <a href="/ProjetPrison/views/new_post.php">🆕 Proposer un sujet</a>
                <a href="/ProjetPrison/admin/validate_post.php">
                    📋 Valider les posts
                    <?php if ($pendingPostsCount > 0): ?>
                        <span class="notif-red"><?= $pendingPostsCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/ProjetPrison/views/admin/work_page.php">📊 Espace de travail</a>
                <a href="/ProjetPrison/admin/manage_comments.php">Gérer les commentaires</a>
            <?php endif; ?>

            <span class="username-tag">
                👤 <?= htmlspecialchars($_SESSION['user']['username']) ?>
                <?php if ($isAdmin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php elseif ($isGestionnaire): ?>
                    <span class="gestionnaire-badge">GESTION</span>
                <?php elseif ($isPrisonnier): ?>
                    <span class="prisonnier-badge">PRISONNIER</span>
                <?php endif; ?>
            </span>
        </div>
    <?php endif; ?>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateMessageCount() {
        fetch('/ProjetPrison/ajax/compteur_messages.php')
            .then(response => response.json())
            .then(data => {
                const msgCount = document.getElementById('msg-count');
                if (data.unread > 0) {
                    msgCount.textContent = data.unread;
                    msgCount.style.display = '';
                } else {
                    msgCount.style.display = 'none';
                }
            });
    }

    function updateNotifCount() {
        fetch('/ProjetPrison/ajax/compteur_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notifCount = document.getElementById('notif-count');
                if (data.unread > 0) {
                    notifCount.textContent = data.unread;
                    notifCount.style.display = '';
                } else {
                    notifCount.style.display = 'none';
                }
            });
    }

    updateMessageCount();
    updateNotifCount();
    setInterval(updateMessageCount, 30000); // Toutes les 30 sec
    setInterval(updateNotifCount, 30000);
});
</script>
