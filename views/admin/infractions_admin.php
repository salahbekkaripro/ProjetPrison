<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

// Vérifie que seul le rôle 'admin' peut accéder
checkRole('admin');


require_user_login();
$role = $_SESSION['user']['role'] ?? '';

if (!in_array($role, ['admin', 'chef'])) {
    echo "⛔ Accès interdit.";
    exit;
}

$pageTitle = "📂 Gestion des infractions (par prisonnier)";

// Récupérer les prisonniers ayant au moins une infraction
$stmt = $pdo->query("
    SELECT DISTINCT p.id AS prisonnier_id, u.nom, u.prenom
    FROM infraction i
    JOIN prisonnier p ON i.prisonnier_id = p.id
    JOIN users u ON p.utilisateur_id = u.id
    ORDER BY u.nom
");

$prisonniers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si un prisonnier est sélectionné, on récupère ses infractions
$selected_id = isset($_GET['prisonnier_id']) ? intval($_GET['prisonnier_id']) : null;
$infractions = [];

if ($selected_id) {
    $stmt = $pdo->prepare("
        SELECT i.type_infraction, i.date_infraction, i.sanction,
               u.nom AS nom, u.prenom AS prenom
        FROM infraction i
        JOIN prisonnier p ON i.prisonnier_id = p.id
        JOIN users u ON p.utilisateur_id = u.id
        WHERE i.prisonnier_id = ?
        ORDER BY i.date_infraction DESC
    ");
    $stmt->execute([$selected_id]);
    $infractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$customHeadStyle = <<<CSS
dashboard-container { padding: 20px; max-width: 1000px; margin: auto; }
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
    <h2><?= $pageTitle ?></h2>

    <form method="GET" action="">
        <label for="prisonnier_id">👤 Sélectionner un prisonnier :</label>
        <select name="prisonnier_id" id="prisonnier_id" required>
            <option value="">-- Choisir un prisonnier --</option>
            <?php foreach ($prisonniers as $p): ?>
                <option value="<?= $p['prisonnier_id'] ?>" <?= ($p['prisonnier_id'] == $selected_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">🔎 Voir les infractions</button>
    </form>

    <?php if ($selected_id): ?>
        <h3>📋 Infractions de <?= htmlspecialchars($infractions[0]['prenom'] . ' ' . $infractions[0]['nom'] ?? '') ?></h3>

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

// Optionnel : toast d’arrivée
<?php if ($selected_id): ?>
    showToast("✅ Infractions chargées avec succès", 'success');
<?php endif; ?>
</script>

</body>
</html>
