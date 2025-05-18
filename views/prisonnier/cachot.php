<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT s.fin_sanction
    FROM sanction s
    JOIN prisonnier p ON s.prisonnier_id = p.id
    WHERE p.utilisateur_id = ? 
      AND s.type_sanction = 'mise_au_trou'
    ORDER BY s.date_sanction DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if (!$row || !$row['fin_sanction']) {
    echo "<p style='color:white;'>⛔ Aucune sanction active.</p>";
    exit;
}

$fin = $row['fin_sanction'];

$customHeadStyle = <<<CSS
body {
    margin: 0;
    padding: 0;
    background: #000;
    color: #f00;
    font-family: 'Courier New', monospace;
    height: 100vh;
    overflow: hidden;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

body::before {
    content: "";
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-image: repeating-linear-gradient(
        to right,
        rgba(255, 0, 0, 0.2) 0px,
        rgba(255, 0, 0, 0.2) 3px,
        transparent 3px,
        transparent 80px
    );
    z-index: 1;
    animation: prisonBars 2s linear infinite;
}

@keyframes prisonBars {
    0% { background-position: 0 0; }
    100% { background-position: 80px 0; }
}

body::after {
    content: "";
    position: absolute;
    top: 0; left: -100%;
    width: 200%; height: 100%;
    background: linear-gradient(120deg, transparent 45%, rgba(255, 255, 255, 0.03) 50%, transparent 55%);
    z-index: 2;
    animation: lightSweep 5s linear infinite;
}

@keyframes lightSweep {
    0% { left: -100%; }
    100% { left: 100%; }
}

.container {
    z-index: 5;
    padding: 40px;
    border: 3px solid #f00;
    border-radius: 20px;
    background-color: rgba(0, 0, 0, 0.9);
    box-shadow: 0 0 50px rgba(255, 0, 0, 0.8);
    text-align: center;
    max-width: 90%;
    animation: pulseGlow 2s ease-in-out infinite;
}

@keyframes pulseGlow {
    0%, 100% {
        box-shadow: 0 0 20px #f00, inset 0 0 10px rgba(255,0,0,0.6);
    }
    50% {
        box-shadow: 0 0 50px #f00, inset 0 0 30px rgba(255,0,0,0.8);
    }
}

h1 {
    font-size: 3.5rem;
    color: #ff4d4d;
    animation: blink 1s infinite;
    text-shadow: 0 0 10px #f00, 0 0 20px #900;
    letter-spacing: 2px;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

p {
    font-size: 1.6rem;
    margin: 15px 0;
    color: #fff;
}

#countdown {
    font-size: 2.5rem;
    font-weight: bold;
    margin-top: 20px;
    color: #fffa75;
    text-shadow: 0 0 10px #ff0, 0 0 20px #f00;
    font-family: 'Courier New', monospace;
}
CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
    <div class="container">
        <h1>🚨 CACHOT 🚨</h1>
        <p>Vous êtes enfermé suite à une sanction disciplinaire.<br>
        Temps restant avant votre libération :</p>
        <div id="countdown">Chargement...</div>
    </div>

    <script>
        const finSanction = new Date("<?= $fin ?>").getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const diff = finSanction - now;

            if (diff <= 0) {
                document.getElementById("countdown").innerText = "✅ Libération imminente...";
                setTimeout(() => location.reload(), 5000);
                return;
            }

            const h = Math.floor(diff / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);

            document.getElementById("countdown").innerText =
                `${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
</body>
</html>
