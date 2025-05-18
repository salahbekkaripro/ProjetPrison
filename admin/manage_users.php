<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_role.php';
require_once '../includes/header.php';

checkRole('admin');

// Bannir
if (isset($_POST['ban_user']) && isset($_POST['ban_duration'])) {
    $id = intval($_POST['ban_user']);
    if ($id != $_SESSION['user']['id']) {
        $duration = $_POST['ban_duration'] === 'custom' ? $_POST['custom_duration'] : $_POST['ban_duration'];

if ($duration === '1d') {
    $ban_until = date('Y-m-d H:i:s', strtotime('+1 day'));
} elseif ($duration === '7d') {
    $ban_until = date('Y-m-d H:i:s', strtotime('+7 days'));
} elseif ($duration === 'perm') {
    $ban_until = NULL;
} else {
    // ➔ cas où l'admin a tapé un truc genre "+5 days 3 hours"
    $timestamp = strtotime($duration);
    $ban_until = $timestamp ? date('Y-m-d H:i:s', $timestamp) : NULL;
}

        $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, ban_until = ? WHERE id = ?");
        $stmt->execute([$ban_until, $id]);
        $target = getUsernameById($pdo, $id);
        $log = $pdo->prepare(" INSERT INTO admin_logs (action_type, admin_username, target_username, created_at) VALUES ('ban_user', ?, ?, NOW())");
        $log->execute([$_SESSION['user']['username'], $target]);
    }
    header(header: 'Location: manage_users.php');
    exit;
}

// Débannir
if (isset($_GET['unban'])) {
    $id = intval($_GET['unban']);
    if ($id != $_SESSION['user']['id']) {
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 0, ban_until = NULL WHERE id = ?");
        $stmt->execute([$id]);
        $target = getUsernameById($pdo, $id);
        $log = $pdo->prepare("INSERT INTO admin_logs (action_type, admin_username, target_username, created_at) VALUES ('unban_user', ?, ?, NOW())");
        $log->execute([$_SESSION['user']['username'], $target]);
    }
    header('Location: manage_users.php');
    exit;
}

// Promouvoir
if (isset($_GET['promote'])) {
    $id = intval($_GET['promote']);
    if ($id != $_SESSION['user']['id']) {
        // sécurité : forcer l'ancien rôle à 'membre' si inexistant
        $stmtCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmtCheck->execute([$id]);
        $role = $stmtCheck->fetchColumn() ?: 'membre';

        if ($role === 'membre') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([$id]);

            $target = getUsernameById($pdo, $id);
            $log = $pdo->prepare("INSERT INTO admin_logs (action_type, admin_username, target_username, created_at) VALUES ('promote_user', ?, ?, NOW())");
            $log->execute([$_SESSION['user']['username'], $target]);
        }
    }
    header('Location: manage_users.php');
    exit;
}


// Rétrograder
if (isset($_GET['demote'])) {
    $id = intval($_GET['demote']);
    if ($id != $_SESSION['user']['id']) {
        // sécurité : forcer l'ancien rôle à 'membre' si inexistant
        $stmtCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmtCheck->execute([$id]);
        $role = $stmtCheck->fetchColumn() ?: 'membre';

        if ($role === 'admin') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'membre' WHERE id = ?");
            $stmt->execute([$id]);

            $target = getUsernameById($pdo, $id);
            $log = $pdo->prepare("INSERT INTO admin_logs (action_type, admin_username, target_username, created_at) VALUES ('demote_user', ?, ?, NOW())");
            $log->execute([$_SESSION['user']['username'], $target]);
        }
    }
    header('Location: manage_users.php');
    exit;
}


// Avertir
if (isset($_GET['warn'])) {
    $id = intval($_GET['warn']);
    if ($id != $_SESSION['user']['id']) {
        $target = getUsernameById($pdo, $id);
        $log = $pdo->prepare("INSERT INTO admin_logs (action_type, admin_username, target_username, created_at) VALUES ('warn_user', ?, ?, NOW())");
        $log->execute([$_SESSION['user']['username'], $target]);
    }
    header('Location: manage_users.php');
    exit;
}

// Liste utilisateurs
$stmt = $pdo->query("SELECT id, username, email, role, is_banned, ban_until FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

function getUsernameById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ? $user['username'] : 'unknown';
}

$customHeadStyle = <<<CSS

body { background: #0d0d0d; }
.container { max-width: 1200px; margin: 50px auto; }
.user-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}
.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 0.8em;
}
.badge-role {
    background: rgba(255,165,0,0.2);
    color: orange;
}
.badge-status {
    background: rgba(0,128,0,0.2);
    color: #00ff88;
}
.badge-banned {
    background: rgba(255,0,0,0.2);
    color: #ff5555;
}
.badge-you {
    background: rgba(138,43,226,0.2);
    color: #c18bff;
}
.actions button, .actions select, .actions a {
    margin: 5px 5px 0 0;
    padding: 5px 10px;
    border: none;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 0.9em;
    cursor: pointer;
}
.actions button:hover, .actions select:hover, .actions a:hover {
    background: rgba(255,165,0,0.3);
}
#search-input {
    width: 100%;
    padding: 12px;
    margin-bottom: 30px;
    background: rgba(255,255,255,0.05);
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 16px;
}

.actions select {
    background-color: rgba(91, 76, 47, 0.1); /* Orange léger */
    color: white; /* Texte blanc */
    border: 1px solid rgba(216, 156, 43, 0.12);
    border-radius: 8px;
    padding: 5px 10px;
    appearance: none; /* Supprimer style natif navigateur */
    -webkit-appearance: none;
    -moz-appearance: none;
}

.actions select option {
    background-color:rgb(117, 109, 49); /* Fond sombre menu déroulé */
    color: white; /* Texte des options */
}

CSS;
    
    
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container">
    <h2 style="color:white;">👤 Gestion des utilisateurs</h2>
    <input type="text" id="search-input" placeholder="🔎 Rechercher un utilisateur...">
    <?php foreach ($users as $user): ?>
    <div class="user-card">
        <div>
            <strong><?= htmlspecialchars($user['username']) ?></strong><br>
            <?= htmlspecialchars($user['email']) ?><br>
            <?php
$role = $user['role'] ?: 'membre'; // si vide, on force 'membre'
$label = $role === 'admin' ? 'ADMIN' : 'MEMBRE';
?>
<span class="badge badge-role"><?= $label ?></span>
            <?php if ($user['is_banned']): ?>
                <span class="badge badge-banned">
    🚫 Banni
    <?php if (!empty($user['ban_until'])): ?>
        <br><small style="color: #ffaaaa;">jusqu'au <?= date('d/m/Y H:i', strtotime($user['ban_until'])) ?></small>
        <br><small 
    id="countdown-<?= $user['id'] ?>" 
    data-banuntil="<?= htmlspecialchars($user['ban_until']) ?>" 
    style="color: #ffdd88; font-size: 11px;">
</small>

    <?php else: ?>
        <br><small style="color: #ffaaaa;">bannissement permanent</small>
    <?php endif; ?>
</span>
            <?php else: ?>
                <span class="badge badge-status">✅ Actif</span>
            <?php endif; ?>
            <?php if ($user['id'] == $_SESSION['user']['id']): ?>
                <span class="badge badge-you">👤 Vous</span>
            <?php endif; ?>
        </div>
        <div class="actions">
            <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                <?php if (!$user['is_banned']): ?>
                    <form method="POST" action="manage_users.php" style="display:inline;">
                        <input type="hidden" name="ban_user" value="<?= $user['id'] ?>">
                        <select name="ban_duration" id="ban_duration_select" required>
    <option value="">Durée</option>
    <option value="1d">24h</option>
    <option value="7d">7 jours</option>
    <option value="perm">Permanent</option>
    <option value="custom">Autre...</option>
</select>

<input type="text" id="custom_duration" name="custom_duration" placeholder="Ex: +5 days, +2 weeks..." style="display:none; margin-top:5px; background:rgba(255,255,255,0.05); border:none; border-radius:8px; padding:5px 10px; color:white;">

<small id="custom_help" style="display:none; color: #aaaaaa; font-size: 12px;">
Format: +1 year 3 days, +2 weeks 4 days, +10 minutes...
</small>


<button type="submit" class="btn-neon" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,165,0,0.2);">🛑 Bannir</button>
                    </form>
                    <a href="manage_users.php?warn=<?= $user['id'] ?>">⚠️ Avertir</a>
                <?php else: ?>
                    <a href="manage_users.php?unban=<?= $user['id'] ?>">✅ Débannir</a>
                <?php endif; ?>
                <?php
$role = $user['role'] ?: 'membre';
if ($role === 'membre'): ?>
  <a href="manage_users.php?promote=<?= $user['id'] ?>">🛡️ Promouvoir</a>
<?php elseif ($role === 'admin'): ?>
  <a href="manage_users.php?demote=<?= $user['id'] ?>">🔻 Rétrograder</a>
<?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<script>
const searchInput = document.getElementById('search-input');
const banSelects = document.querySelectorAll('#ban_duration_select');
const userCards = document.querySelectorAll('.user-card');

// Gestion affichage custom duration
banSelects.forEach(select => {
    select.addEventListener('change', function() {
        const input = this.parentElement.querySelector('#custom_duration');
        const help = this.parentElement.querySelector('#custom_help');

        if (this.value === 'custom') {
            input.style.display = 'block';
            help.style.display = 'block';
            input.required = true;
        } else {
            input.style.display = 'none';
            help.style.display = 'none';
            input.required = false;
            input.style.border = 'none'; // Enlève les bordures rouge/vert si on change d'option
        }
    });
});

// Filtrage utilisateurs
searchInput.addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    userCards.forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(filter) ? 'flex' : 'none';
    });
});

// Validation du formulaire à l'envoi
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const select = form.querySelector('#ban_duration_select');
        const customInput = form.querySelector('#custom_duration');

        if (select && customInput && select.value === 'custom') {
            const value = customInput.value.trim();

            if (!isValidDuration(value)) {
                e.preventDefault(); // Bloque l'envoi du formulaire
                alert("⛔ Erreur : format de durée invalide. Exemple attendu : +5 days 18 hours, +2 weeks 4 days, +10 hours...");
                customInput.focus();
            }
        }
    });
});

// Vérification en live + correction automatique
const customInputs = document.querySelectorAll('#custom_duration');

customInputs.forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.length > 0 && this.value[0] !== '+') {
            this.value = '+' + this.value;
        }

        if (isValidDuration(this.value.trim())) {
            this.style.border = "2px solid #00ff88"; // Vert
        } else {
            this.style.border = "2px solid red"; // Rouge
        }
    });
});

// Fonction pour valider les formats combinés
function isValidDuration(input) {
    const pattern = /^\+\d+\s*(year|years|day|days|week|weeks|hour|hours|minute|minutes)(\s+\d+\s*(year|years|day|days|week|weeks|hour|hours|minute|minutes))*$/i;
    return pattern.test(input.trim());
}
// Countdown pour les bannis
document.querySelectorAll('[id^="countdown-"]').forEach(el => {
    const banUntilText = el.dataset.banuntil;
    
    if (banUntilText) {
        const targetDate = new Date(banUntilText.replace(' ', 'T')); // <= ICI AJOUT DU .replace
        
        function updateCountdown() {
            const now = new Date();
            const diff = targetDate - now;

            if (diff <= 0) {
                el.innerText = "⏳ Expiré";
                el.style.color = "#ff5555";
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((diff / (1000 * 60)) % 60);

            el.innerText = `reste ${days}j ${hours}h ${minutes}m`;
        }

        updateCountdown();
        setInterval(updateCountdown, 60000); // Mettre à jour toutes les minutes
    }
});


</script>


<?php include '../includes/footer.php'; ?>
</html>