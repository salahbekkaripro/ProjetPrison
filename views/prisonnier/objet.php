<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

checkRole('prisonnier');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    echo "⛔ Accès interdit.";
    exit;
}

$user_id = $_SESSION['user']['id'];
$prisonnier_id = $_SESSION['user']['prisonnier_id'] ?? null;

if (!$prisonnier_id) {
    echo "❌ Prisonnier introuvable.";
    exit;
}

$stmt = $pdo->prepare("
    SELECT op.*, od.interdit, od.description, od.nom AS nom_objet
    FROM objets_prisonniers op
    JOIN objets_disponibles od ON od.id = op.objet_id
    WHERE op.prisonnier_id = ?
");
$stmt->execute([$prisonnier_id]);
$objets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomObjets = array_column($objets, 'nom_objet');
$hasKey = in_array('Clé artisanale', $nomObjets);
$hasPlan = in_array('Plan de la prison', $nomObjets);
$canEscape = $hasKey && $hasPlan;

// 🎯 Récupère cibles disponibles
$stmtCibles = $pdo->prepare("
    SELECT u.nom, u.prenom, p.id AS prisonnier_id
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE p.id != ? AND p.etat != 'décédé'
");
$stmtCibles->execute([$prisonnier_id]);
$cibles = $stmtCibles->fetchAll(PDO::FETCH_ASSOC);

$customHeadStyle = <<<CSS


       body {
            background-color: #121212;
            color: #f0f0f0;
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            max-width: 850px;
            margin: auto;
            padding: 20px;
        }

        .card {
            background-color: #1e1e1e;
            border-left: 5px solid #ff3d3d;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px #00000066;
        }

        .card p { margin: 5px 0; }
        .danger { color: #ff4d4d; font-weight: bold; }
        .safe { color: #4caf50; }

        .btn-attaque {
            background-color: #b71c1c;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-attaque:hover {
            background-color: #f44336;
        }

        /* 🔐 Overlay style */
        #evasion-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .evasion-content {
            text-align: center;
            animation: fadeIn 1s ease;
        }

        .evasion-content h2 {
            color: #58ff99;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .evasion-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            margin: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.2s ease;
        }

        .evasion-btn:hover {
            transform: scale(1.05);
        }

        .evasion-btn.accept {
            background-color: #28a745;
            color: white;
        }

        .evasion-btn.cancel {
            background-color: #dc3545;
            color: white;
        }

        .key-img {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
            animation: zoomFade 1s ease-out forwards;
            opacity: 0;
        }

        @keyframes zoomFade {
            0% { transform: scale(0.6); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }
CSS;
    
    
    
?>
<!DOCTYPE html>
<html lang="fr">
    <?php include '../../includes/head.php'; ?>
</head>
<body>

<?php include '../../includes/navbar.php'; ?>

<div class="container">
    <h2>🧳 Vos objets</h2>

    <?php if (empty($objets)): ?>
        <p>Aucun objet détenu.</p>
    <?php else: ?>
        <?php foreach ($objets as $o): ?>
            <div class="card">
                <p><strong><?= htmlspecialchars($o['nom_objet']) ?></strong></p>
                <p><?= htmlspecialchars($o['description']) ?></p>
                <p>🔐 Statut :
                    <?= $o['interdit'] ? "<span class='danger'>Objet interdit</span>" : "<span class='safe'>Autorisé</span>" ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    $objetsDangereux = array_filter($objets, fn($o) => $o['interdit']);
    if (count($objetsDangereux) > 0 && count($cibles) > 0): ?>
        <hr>
        <h3>⚔️ Attaquer un prisonnier</h3>
        <p>Vous avez en votre possession un objet interdit. Vous pouvez tenter une attaque...</p>

        <form id="attaqueForm" method="POST" action="jouer_attaque.php">
            <label for="cible">Choisissez votre cible :</label>
            <select name="cible_id" required>
                <?php foreach ($cibles as $c): ?>
                    <option value="<?= $c['prisonnier_id'] ?>">
                        <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn-attaque" onclick="playAndSubmit()">Lancer l'attaque</button>
        </form>

        <audio id="startSound" src="../../assets/sounds/start.mp3" preload="auto"></audio>

        <script>
            function playAndSubmit() {
                const audio = document.getElementById('startSound');
                audio.play().catch(() => {});
                setTimeout(() => {
                    document.getElementById("attaqueForm").submit();
                }, 300);
            }
        </script>
    <?php endif; ?>
</div>

<?php if ($canEscape): ?>
<!-- 🔐 Overlay animé -->
<div id="evasion-overlay">
    <div class="evasion-content">
        <img src="../../assets/images/cle.png" alt="Clé artisanale" class="key-img">
        <h2>🔓 Une opportunité s'offre à toi...</h2>
        <p>Tu as une <strong>Clé artisanale</strong> et un <strong>Plan de la prison</strong>.</p>
        <p>Souhaites-tu tenter l'évasion ?</p>
        <button class="evasion-btn accept" onclick="window.location.href='evasion.php'">✅ Oui, je m’évade</button>
        <button class="evasion-btn cancel" onclick="fermerOverlay()">❌ Non, rester ici</button>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
    const overlay = document.getElementById('evasion-overlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
});

    function fermerOverlay() {
        document.getElementById('evasion-overlay').style.display = 'none';
    }
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
</body>
</html>
