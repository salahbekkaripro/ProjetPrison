<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$current_user_id = $_SESSION['user']['id'];

// GESTION AJAX : suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!empty($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND recipient_id = ?");
        $stmt->execute([$id, $current_user_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if (!empty($_POST['delete_all'])) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE recipient_id = ?");
        $stmt->execute([$current_user_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}
require_once '../includes/header.php';


// Récupération des notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC");
$stmt->execute([$current_user_id]);
$notifications = $stmt->fetchAll();

// Marquer comme lues
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_id = ? AND is_read = 0")
    ->execute([$current_user_id]);

// Supprimer certaines notifications inutiles (ex: réponses de pot-de-vin liées à des infractions)
$potIdsAvecInfraction = [];
foreach ($notifications as $notif) {
    if ($notif['type'] === 'infraction_suggeree' && !empty($notif['pot_id'])) {
        $potIdsAvecInfraction[] = $notif['pot_id'];
    }
}
// Filtrer et supprimer notifications inutiles
$notifications = array_filter($notifications, function($notif) use ($potIdsAvecInfraction, $pdo) {
    if ($notif['type'] === 'reponse_pot_de_vin' && in_array($notif['pot_id'], $potIdsAvecInfraction)) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notif['id']]);
        return false;
    }
    return true;
});

require_once '../includes/head.php';
require_once '../includes/navbar.php';
?>

<div class="container" style="max-width: 900px; margin: auto; margin-top: 60px;">
    <h2 style="color:white; font-size:2rem; margin-bottom: 20px;">🔔 Vos notifications</h2>

    <?php if (empty($notifications)): ?>
        <p style="color:white;">Aucune notification pour le moment.</p>
    <?php else: ?>
        <div id="notif-container">
            <?php foreach ($notifications as $notif): 
                $class = 'notif-card glass-box';
                if ($notif['type'] === 'infraction_suggeree') $class .= ' notif-infraction';
                if ($notif['type'] === 'sanction_appliquee') $class .= ' notif-sanction';
                if ($notif['type'] === 'reponse_pot_de_vin' && strpos($notif['message'], 'refusé') !== false) $class .= ' notif-urgence';
            ?>
                <div class="<?= $class ?>" id="notif-<?= $notif['id'] ?>" style="margin-bottom: 15px; position: relative; padding: 15px;">
                    <p style="color:white; margin:0 40px 0 0;">
                        <?php
                        switch ($notif['type']) {
                            case 'reply':
                                echo "💬 Nouvelle réponse à <a href='post.php?id=" . $notif['post_id'] . "#comment-" . $notif['comment_id'] . "' style='color:#ffa;'>ce commentaire</a>";
                                break;
                            case 'message':
                                echo "📩 Nouveau message privé reçu";
                                break;
                            case 'sante_degradee':
                                echo "💀 <strong>Santé dégradée :</strong> " . htmlspecialchars_decode($notif['message']);
                                break;
                            case 'attaque_subie':
                                echo "🗡️ <strong>Attaque subie :</strong> " . htmlspecialchars_decode($notif['message']);
                                break;
                            case 'achat':
                                echo "🛒 " . htmlspecialchars($notif['message']);
                                break;
                            case 'pot_de_vin':
                                echo "💸 " . htmlspecialchars($notif['message']);
                                $stmtPot = $pdo->prepare("SELECT statut, expire_at FROM pots_de_vin WHERE id = ?");
                                $stmtPot->execute([$notif['pot_id']]);
                                $pot = $stmtPot->fetch();
                                $expire = $pot['expire_at'] ?? null;
                                $statut = $pot['statut'] ?? null;
                                if ($statut === 'accepté') {
                                    echo "<br><span style='color:lime; font-weight:bold;'>✅ Vous avez payé le pot-de-vin</span>";
                                } elseif ($statut === 'refusé') {
                                    echo "<br><span style='color:red; font-weight:bold;'>❌ Vous avez refusé le pot-de-vin</span>";
                                } elseif ($statut === 'en_attente' && $expire) {
$expireISO = str_replace(' ', 'T', $expire);  // transforme "2025-05-18 10:30:00" en "2025-05-18T10:30:00"
echo "<br><span class='countdown' data-expire='" . htmlspecialchars($expireISO) . "' style='color:yellow; font-weight:bold;'>Chargement...</span>";
                                    if ($_SESSION['user']['role'] === 'prisonnier' && strtotime($expire) > time()) {
                                        echo "<form method='post' action='../actions/repondre_pot_de_vin.php' class='pot-form' style='margin-top:5px;'>
                                            <input type='hidden' name='pot_id' value='" . htmlspecialchars($notif['pot_id']) . "'>
                                            <button type='submit' name='action' value='payer' class='btn-small'>Payer</button>
                                            <button type='submit' name='action' value='refuser' class='btn-small'>Refuser</button>
                                        </form>";
                                    }
                                }
                                break;
                            case 'annonce_generale':
                                echo "📢 <strong>Annonce générale :</strong> " . htmlspecialchars_decode($notif['message']);
                                break;
                            case 'pot_de_vin_admin':
                                echo "🕒 " . htmlspecialchars($notif['message']);
                                break;
                            case 'reponse_pot_de_vin':
                                $stmt = $pdo->prepare("SELECT u.nom, u.prenom FROM pots_de_vin pd JOIN prisonnier p ON pd.prisonnier_id = p.id JOIN users u ON p.utilisateur_id = u.id WHERE pd.id = ?");
                                $stmt->execute([$notif['pot_id']]);
                                $pot = $stmt->fetch();
                                $nomComplet = $pot ? $pot['prenom'] . ' ' . $pot['nom'] : 'un prisonnier inconnu';
                                echo "📨 Notification concernant le prisonnier <strong>$nomComplet</strong><br>";
                                echo htmlspecialchars_decode($notif['message']);
                                break;
                            case 'sanction_appliquee':
                                echo "⚖️ " . htmlspecialchars($notif['message']);
                                break;
                            case 'duel_invitation':
                                echo "⚔️ Invitation à un duel : " . htmlspecialchars($notif['message']);
                                break;
                            case 'duel_confirmation':
                                echo "⚔️ " . htmlspecialchars($notif['message']);
                                break;
                            case 'infraction_suggeree':
                                $stmt = $pdo->prepare("
                                    SELECT u.nom, u.prenom, p.utilisateur_id, p.id AS prisonnier_id
                                    FROM pots_de_vin pd
                                    JOIN prisonnier p ON pd.prisonnier_id = p.id
                                    JOIN users u ON p.utilisateur_id = u.id
                                    WHERE pd.id = ?
                                ");
                                $stmt->execute([$notif['pot_id']]);
                                $prisonnier = $stmt->fetch();
                                if ($prisonnier) {
                                    $prisonnier_id = $prisonnier['prisonnier_id'];
                                    $nomComplet = htmlspecialchars($prisonnier['prenom'] . ' ' . $prisonnier['nom']);
                                    echo "🚨 <strong>$nomComplet</strong> : " . htmlspecialchars($notif['message']);
                                    $stmtCheck = $pdo->prepare("SELECT id FROM infraction WHERE pot_id = ?");
                                    $stmtCheck->execute([$notif['pot_id']]);
                                    $infractionExist = $stmtCheck->fetch();
                                    if (!$infractionExist) {
                                        echo "<br><a href='admin/nouvelle_infraction.php?prisonnier_id={$prisonnier_id}&pot_id={$notif['pot_id']}' class='btn-small' style='margin-top: 10px; display:inline-block; font-weight:bold; color:white; background:#e74c3c; padding:6px 12px; border-radius:6px;'>⚠️ Créer une infraction</a>";
                                    } else {
                                        echo "<br><span style='display:inline-block; margin-top:10px; color:lime; font-weight:bold;'>✅ Infraction déjà créée</span>";
                                    }
                                }
                                break;
                            default:
                                echo htmlspecialchars($notif['message']);
                                break;
                        }

                        if ($notif['is_read'] == 0) {
                            echo " <span style='color: orange;'>• Non lue</span>";
                        }
                        ?>
                    </p>

                    <button class="delete-notif-btn btn-neon" data-id="<?= $notif['id'] ?>" style="position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 20px; cursor: pointer; color: #f44336;">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button id="delete-all-btn" class="btn-neon" style="margin-top: 20px;">🧹 Nettoyer toutes</button>
    <?php endif; ?>
</div>

<script src="assets/js/notifications.js"></script>

<script>
function updateCountdowns() {
    const now = new Date().getTime();
    document.querySelectorAll('.countdown').forEach(span => {
        const expire = new Date(span.dataset.expire).getTime();
        const diff = expire - now;
        if (diff <= 0) {
            if (!span.classList.contains('expired')) {
                span.innerText = "⛔ Expiré";
                span.style.color = "red";
                span.classList.add('expired');
                const parent = span.closest('.notif-card');
                const form = parent.querySelector('form.pot-form');
                if (form) {
                    form.querySelectorAll('button').forEach(btn => btn.disabled = true);
                    form.insertAdjacentHTML('beforebegin', "<p style='color:red; font-weight:bold;'>⛔ Délai expiré</p>");
                    form.remove();
                }
            }
        } else {
            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            span.innerText = `⏳ Temps restant : ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
    });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();

// Gestion des suppressions avec AJAX
document.querySelectorAll('.delete-notif-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('🗑️ Voulez-vous vraiment supprimer cette notification ?')) return;

        const notifId = this.dataset.id;
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ajax: 1, delete_id: notifId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notifDiv = document.getElementById('notif-' + notifId);
                if (notifDiv) notifDiv.remove();
            } else {
                alert('❌ Erreur lors de la suppression : ' + (data.error || 'Inconnue'));
            }
        })
        .catch(() => alert('❌ Erreur réseau lors de la suppression'));
    });
});

document.getElementById('delete-all-btn')?.addEventListener('click', () => {
    if (!confirm('🧹 Supprimer toutes les notifications ?')) return;

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ajax: 1, delete_all: 1})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('notif-container').innerHTML = '<p style="color:white;">Aucune notification pour le moment.</p>';
        } else {
            alert('❌ Erreur lors de la suppression : ' + (data.error || 'Inconnue'));
        }
    })
    .catch(() => alert('❌ Erreur réseau lors de la suppression'));
});
</script>