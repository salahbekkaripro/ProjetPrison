<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

checkRole('prisonnier');
require_user_login();

if ($_SESSION['user']['role'] !== 'prisonnier') {
    echo "⛔ Accès interdit.";
    exit;
}

$current_user_id = $_SESSION['user']['id'];
$pageTitle = "Travail du prisonnier";

$stmt = $pdo->prepare("SELECT argent FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$solde = $stmt->fetchColumn();

$customHeadStyle = <<<CSS

        body {
            background-color: #1a1a1a;
            color: #f0f0f0;
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }

        .dashboard-container {
            padding: 40px;
            max-width: 900px;
            margin: auto;
            background: #2b2b2b;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.1);
            animation: fadeIn 0.8s ease;
        }

        h2 {
            text-align: center;
            color: #ffd700;
            font-size: 2rem;
        }

        #jeu-container {
            text-align: center;
            margin-top: 30px;
        }

        input[type="number"] {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #888;
            background: #111;
            color: #fff;
            width: 80px;
            text-align: center;
            font-size: 1.2rem;
        }

        .btn {
            padding: 10px 20px;
            font-weight: bold;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            margin: 5px;
            transition: all 0.2s ease-in-out;
        }

        .btn.confirm {
            background-color: #28a745;
            color: white;
        }

        .btn.confirm:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        .btn.cancel {
            background-color: #dc3545;
            color: white;
        }

        #resultat {
            text-align: center;
            margin-top: 20px;
            font-size: 1.2rem;
        }

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
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
            color: white;
            text-align: center;
        }

        .toast.success { background-color: #28a745; }
        .toast.error { background-color: #dc3545; }

        #victory-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.9), rgba(0,0,0,0.95));
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            flex-direction: column;
            animation: fadeIn 0.5s ease-in-out;
        }

        .victory-box {
            text-align: center;
            padding: 40px;
            background: #222;
            border: 3px solid #58ff99;
            border-radius: 16px;
            box-shadow: 0 0 20px #58ff99;
            animation: popUp 0.7s ease-in-out;
        }

        .victory-message {
            font-size: 2.5rem;
            font-weight: bold;
            color: #58ff99;
            margin-bottom: 20px;
        }

        .reward-animation {
            font-size: 4rem;
            animation: rewardPop 1s infinite ease-in-out alternate;
        }

        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }

        @keyframes popUp {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes rewardPop {
            from { transform: translateY(-10px); }
            to { transform: translateY(10px); }
        }

CSS;
    
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
<?php include '../../includes/navbar.php'; ?>

<div id="victory-overlay">
    <div class="victory-box">
        <div class="reward-animation">💰</div>
        <div class="victory-message">Bravo !<br>Tu as gagné 50 € !</div>
    </div>
</div>

<div class="dashboard-container">
    <h2>🛠️ Travail du prisonnier</h2>
    <p style="text-align:center;">Solde actuel : <strong><?= number_format($solde, 2) ?> €</strong></p>

    <div id="jeu-container">
        <p>Devinez un nombre entre <strong>1 et 5</strong>.<br> Si vous avez raison, vous gagnez <strong>50 €</strong> !</p>
        <input type="number" id="nombre" min="1" max="5" required>
        <br><br>
        <button class="btn confirm" onclick="jouer()">🎯 Jouer</button>
    </div>

    <div id="resultat"></div>
</div>

<div id="notif-toast" class="toast success" style="display: none;">
    <span id="notif-message">✅ Action réussie</span>
</div>

<script>
function jouer() {
    const nombre = parseInt(document.getElementById('nombre').value);
    if (!nombre || nombre < 1 || nombre > 5) {
        showToast("❌ Entrez un nombre valide entre 1 et 5", "error");
        return;
    }

    fetch('../../ajax/travail_jeu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre: nombre })
    })
    .then(res => res.json())
    .then(data => {
        const msgBox = document.getElementById('resultat');
        msgBox.innerHTML = data.message;
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            document.getElementById('victory-overlay').style.display = 'flex';
            setTimeout(() => location.reload(), 3500);
        }
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('notif-toast');
    const msg = document.getElementById('notif-message');
    msg.innerText = message;
    toast.className = 'toast ' + type;
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 3000);
}
</script>

</body>
</html>