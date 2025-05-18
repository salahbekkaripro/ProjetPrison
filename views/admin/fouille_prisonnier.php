<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';
require_once '../../includes/check_role.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';   
checkRole('admin');

$current_user_id = $_SESSION['user']['id'];

$stmtAdmin = $pdo->prepare("SELECT nom, prenom FROM users WHERE id = ?");
$stmtAdmin->execute([$current_user_id]);
$adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
$nom_admin = trim(($adminData['nom'] ?? '') . ' ' . ($adminData['prenom'] ?? ''));

if ($nom_admin === '') {
    $nom_admin = $_SESSION['user']['username'];
}

$pageTitle = "Fouille d'un prisonnier";
$prisonnier_id = null;
$objetTrouves = [];
$pot_id = null;

$prisonniers = $pdo->query("
    SELECT p.id, u.nom, u.prenom 
    FROM prisonnier p 
    JOIN users u ON p.utilisateur_id = u.id 
    ORDER BY u.nom
")->fetchAll(PDO::FETCH_ASSOC);

if (!empty($_GET['prisonnier_id']) && is_numeric($_GET['prisonnier_id'])) {
    $prisonnier_id = intval($_GET['prisonnier_id']);

    $stmt = $pdo->prepare("SELECT nom_objet, description, interdit FROM objets_prisonniers WHERE prisonnier_id = ?");
    $stmt->execute([$prisonnier_id]);
    $objetTrouves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT p.utilisateur_id, u.nom, u.prenom 
        FROM prisonnier p 
        JOIN users u ON p.utilisateur_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$prisonnier_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $nom_prisonnier = $data['nom'] . ' ' . $data['prenom'];
        $prisonnier_user_id = $data['utilisateur_id'];

        $stmtNotifadmin = $pdo->prepare("
            INSERT INTO notifications (recipient_id, sender_id, type, message, post_id, is_read, created_at)
            VALUES (?, ?, 'fouille', ?, NULL, 0, NOW())
        ");
        $stmtNotifadmin->execute([
            $current_user_id,
            $current_user_id,
            "Vous avez fouillé $nom_prisonnier."
        ]);

        $stmtNotifPrisonnier = $pdo->prepare("
            INSERT INTO notifications (recipient_id, sender_id, type, message, post_id, is_read, created_at)
            VALUES (?, ?, 'fouille', ?, NULL, 0, NOW())
        ");
        $stmtNotifPrisonnier->execute([
            $prisonnier_user_id,
            $current_user_id,
            "Vous avez été fouillé par le admin $nom_admin."
        ]);

        // Dernier pot-de-vin en attente (si existe)
        $stmtPot = $pdo->prepare("SELECT id FROM pots_de_vin WHERE prisonnier_id = ? AND statut = 'en_attente' ORDER BY date_demande DESC LIMIT 1");
        $stmtPot->execute([$prisonnier_id]);
        $pot_id = $stmtPot->fetchColumn();
    } else {
        echo "<p style='color:red;'>❌ Prisonnier introuvable (ID $prisonnier_id).</p>";
    }
}

    $customHeadStyle = <<<CSS
body {
    background: linear-gradient(135deg, #1c1c1c, #2f2f2f);
    color: #f0f0f0;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
}

h2, h3 {
    text-align: center;
    color: #ffd700;
    margin-bottom: 20px;
}

label, select, button {
    font-size: 1rem;
}

select, button {
    padding: 8px;
    border-radius: 6px;
    border: none;
    margin: 5px 0;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

button {
    cursor: pointer;
}

.btn.confirm {
    background-color: #28a745;
    color: white;
}

.btn.cancel {
    background-color: #dc3545;
    color: white;
}

.btn:hover {
    background-color: #218838;
}

.btn.cancel:hover {
    background-color: #c82333;
}

.dashboard-container {
    max-width: 900px;
    margin: 40px auto;
    background: #2b2b2b;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
}

table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
    background-color: #1e1e1e;
    color: #fff;
    border-radius: 10px;
    overflow: hidden;
}

table th, table td {
    padding: 12px 16px;
    text-align: center;
    border-bottom: 1px solid #444;
}

table th {
    background-color: #444;
}

table tbody tr:hover {
    background-color: #2c2c2c;
}

.modal {
    display: none;
    position: fixed;
    z-index: 10;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: #2b2b2b;
    padding: 25px;
    border-radius: 10px;
    color: #fff;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
}

.modal-actions {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
}

.close-btn {
    float: right;
    font-size: 24px;
    cursor: pointer;
    color: #fff;
}

.toast {
    display: none;
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 15px 25px;
    border-radius: 8px;
    font-weight: bold;
    z-index: 99;
}

.toast.success {
    background-color: #28a745;
    color: white;
}

.toast.error {
    background-color: #dc3545;
    color: white;
}

CSS;
    
    
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>

<script>const BASE_URL = '/ProjetPrison';</script>
<style>

</style>

<body>

<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>👮 Fouille d'un prisonnier</h2>

    <form method="get" action="">
        <label for="prisonnier_id">Choisir un prisonnier :</label>
        <select name="prisonnier_id" id="prisonnier_id" required>
            <option value="">-- Sélectionner --</option>
            <?php foreach ($prisonniers as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($prisonnier_id == $p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Fouiller</button>
    </form>

    <?php if (!empty($objetTrouves)): ?>
        <h3>📦 Objets trouvés :</h3>
        <table>
            <thead><tr><th>Nom</th><th>Description</th><th>Statut</th></tr></thead>
            <tbody>
                <?php foreach ($objetTrouves as $o): ?>
                    <tr>
                        <td><?= htmlspecialchars($o['nom_objet']) ?></td>
                        <td><?= htmlspecialchars($o['description']) ?></td>
                        <td style="color:<?= $o['interdit'] ? 'red' : 'green' ?>; font-weight:bold;">
                            <?= $o['interdit'] ? '❌ Interdit' : '✔️ Autorisé' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (array_filter($objetTrouves, fn($o) => $o['interdit'])): ?>
            <div style="text-align:center; margin-top: 15px;">
                <button onclick="demanderPotDeVin(<?= $prisonnier_id ?>, 50)" class="btn confirm">💰 Demander 50€</button>
                <button onclick="ouvrirModaleInfraction(<?= $prisonnier_id ?>, <?= $pot_id ?? 'null' ?>)" class="btn cancel">❌ Créer une infraction</button>
            </div>
        <?php endif; ?>
    <?php elseif (isset($_GET['prisonnier_id'])): ?>
        <p style="color:red; text-align:center; font-weight:bold;">Aucun objet trouvé pour ce prisonnier.</p>
    <?php endif; ?>
</div>

<div id="bribeModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('bribeModal').style.display = 'none';">&times;</span>
        <h2>💰 Demander un pot-de-vin</h2>
        <p>Souhaitez-vous demander un pot-de-vin de <strong id="montant-affiche">50€</strong> au prisonnier ?</p>
        <div class="modal-actions">
            <button id="confirmBribe" class="btn confirm">Oui</button>
            <button id="cancelBribe" class="btn cancel">Non</button>
        </div>
    </div>
</div>

<div id="infractionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="fermerModaleInfraction()">&times;</span>
        <h2>⚠️ Confirmer l'infraction</h2>
        <p>Créer une infraction et supprimer tous les objets interdits ?</p>
        <div class="modal-actions">
            <button class="btn confirm" onclick="confirmerInfraction()">✅ Confirmer</button>
            <button class="btn cancel" onclick="fermerModaleInfraction()">Annuler</button>
        </div>
    </div>
</div>

<div id="notif-toast" class="toast success">
    <span id="notif-message">✅ Action réussie</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  let prisonnierIdGlobal = null;
  let montantGlobal = null;
  let potIdGlobal = null;

  window.demanderPotDeVin = function(prisonnierId, montant) {
      prisonnierIdGlobal = prisonnierId;
      montantGlobal = montant;
      document.getElementById('montant-affiche').innerText = montant + '€';
      document.getElementById('bribeModal').style.display = 'flex';
  };

  document.getElementById('cancelBribe').onclick = () => {
      document.getElementById('bribeModal').style.display = 'none';
  };

  document.getElementById('confirmBribe').onclick = () => {
      fetch(BASE_URL + '/ajax/demande_pot_de_vin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ prisonnier_id: prisonnierIdGlobal, montant: montantGlobal })
      })
      .then(res => res.json())
      .then(data => {
          document.getElementById('bribeModal').style.display = 'none';
          showToast(data.success ? "✅ Demande envoyée !" : "❌ Erreur : " + data.error, data.success ? 'success' : 'error');
          if (data.success) setTimeout(() => location.reload(), 1000);
      });
  };

  window.ouvrirModaleInfraction = function(prisonnierId, potId = null) {
      prisonnierIdGlobal = prisonnierId;
      potIdGlobal = potId;
      document.getElementById('infractionModal').style.display = 'flex';
  };

  window.fermerModaleInfraction = function() {
      document.getElementById('infractionModal').style.display = 'none';
  };

  window.confirmerInfraction = function() {
      if (!prisonnierIdGlobal) return;
      let url = `${BASE_URL}/views/admin/nouvelle_infraction.php?prisonnier_id=${prisonnierIdGlobal}`;
      if (potIdGlobal) {
          url += `&pot_id=${potIdGlobal}`;
      }
      window.location.href = url;
  };

  function showToast(message, type = 'success') {
      const toast = document.getElementById('notif-toast');
      const msg = document.getElementById('notif-message');
      msg.innerText = message;
      toast.className = 'toast ' + type;
      toast.style.display = 'block';
      setTimeout(() => toast.style.display = 'none', 3000);
  }

  window.showToast = showToast;
});
</script>

</body>
</html>
