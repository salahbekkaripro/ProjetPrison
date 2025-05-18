<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';
// Vérifie que seul le rôle 'prisonnier' peut accéder
checkRole('prisonnier');
require_user_login();

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$prisonnier_id = 0;
$filter_type = $_GET['filtre'] ?? '';

// Récupération ID prisonnier
if ($role === 'prisonnier') {
    $stmt = $pdo->prepare("SELECT id FROM prisonnier WHERE utilisateur_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if (!$row) {
        echo "⚠️ Profil prisonnier non trouvé.";
        exit;
    }
    $prisonnier_id = $row['id'];
} elseif (in_array($role, ['admin', 'chef'])) {
    $prisonnier_id = intval($_GET['prisonnier_id'] ?? 0);
    if ($prisonnier_id <= 0) {
        echo "⛔ ID prisonnier invalide.";
        exit;
    }
} else {
    echo "⛔ Accès interdit.";
    exit;
}

// Récupération des infos du prisonnier
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$prisonnier_id]);
$prisonnier = $stmt->fetch();

if (!$prisonnier) {
    echo "🔍 Prisonnier introuvable.";
    exit;
}

// Récupération des infractions
$query = "SELECT type_infraction, date_infraction, sanction FROM infraction WHERE prisonnier_id = ?";
$params = [$prisonnier_id];

if (!empty($filter_type)) {
    $query .= " AND type_infraction = ?";
    $params[] = $filter_type;
}

$query .= " ORDER BY date_infraction DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$infractions = $stmt->fetchAll();
$customHeadStyle = <<<CSS

        .dashboard-container { padding: 20px; max-width: 1000px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
        th { background-color: rgb(151, 27, 27); color: white; }
        select, button { padding: 8px 12px; font-size: 1rem; margin-top: 10px; }
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1rem;
            z-index: 1000;
            animation: slideUp 0.4s ease-out;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
            color: white;
            text-align: center;
        }
        .toast.success { background-color: #28a745; }
        .toast.error { background-color: #dc3545; }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>

<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>📋 Infractions de <?= htmlspecialchars($prisonnier['prenom'] . ' ' . $prisonnier['nom']) ?></h2>

    <form method="GET" id="filterForm">
        <input type="hidden" name="prisonnier_id" value="<?= $prisonnier_id ?>">
        <label for="filtre">Filtrer par type :</label>
        <select name="filtre" id="filtre">
            <option value="">-- Tous --</option>
            <option value="tentative évasion" <?= $filter_type === 'tentative évasion' ? 'selected' : '' ?>>Tentative d'évasion</option>
            <option value="meurtre" <?= $filter_type === 'meurtre' ? 'selected' : '' ?>>Meurtre</option>
            <option value="possession objet interdit" <?= $filter_type === 'possession objet interdit' ? 'selected' : '' ?>>Objet interdit</option>
            <option value="mutinerie" <?= $filter_type === 'mutinerie' ? 'selected' : '' ?>>Mutinerie</option>
        </select>
    </form>

    <?php if (empty($infractions)): ?>
        <p>Aucune infraction enregistrée.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Type</th><th>Sanction</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($infractions as $inf): ?>
                    <tr>
                        <td><?= htmlspecialchars($inf['type_infraction']) ?></td>
                        <td><?= htmlspecialchars($inf['sanction']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($inf['date_infraction'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="notif-toast" class="toast success" style="display: none;">
    <span id="notif-message">✅ Chargé.</span>
</div>

<script>
function showToast(message, type = 'success') {
    const toast = document.getElementById('notif-toast');
    const msg = document.getElementById('notif-message');
    msg.innerText = message;
    toast.className = 'toast ' + type;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

document.getElementById('filtre').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

// Afficher le toast si on a des infractions chargées
<?php if (!empty($infractions)): ?>
    showToast("✅ Infractions chargées avec succès", 'success');
<?php endif; ?>
</script>

</body>
</html>
