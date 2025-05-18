<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/check_role.php';

// Vérifie que seul le rôle 'prisonnier' peut accéder
checkRole('prisonnier');

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    exit("⛔ Accès interdit.");
}

$cible_id = $_POST['cible_id'] ?? $_SESSION['attaque']['cible_id'] ?? null;
if ($cible_id) {
    $_SESSION['attaque']['cible_id'] = $cible_id;
} else {
    exit("❌ Données d'attaque manquantes.");
}

$customHeadStyle = <<<CSS

        body { background: #1b1b1b; color: white; font-family: 'Segoe UI', sans-serif; text-align: center; margin:0; padding:0; }
        canvas { margin-top: 30px; border: 5px solid #e74c3c; border-radius: 10px; background-color: #2c2c2c; display: block; margin-left:auto; margin-right:auto; }
        #message { margin-top: 20px; font-size: 1.5rem; }
        #overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.95);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            z-index: 999;
        }
        #overlay h2 { font-size: 2rem; margin-bottom: 20px; }
        #overlay ul { text-align: left; max-width: 500px; margin: 0 20px; }
        #overlay button {
            margin-top: 25px; padding: 12px 24px; background-color: #28a745;
            border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold;
            cursor: pointer;
        }
        #countdown { font-size: 2rem; margin-top: 20px; color: #ffcc00; }

CSS;
    
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
<div id="overlay">
    <h2>🎯 Règles du mini-jeu</h2>
    <ul>
        <li>Un couteau est prêt à être lancé au centre.</li>
        <li>Une cible se déplace de gauche à droite.</li>
        <li>Vous devez cliquer lorsque le couteau est aligné avec la cible.</li>
        <li>Une seule chance par attaque.</li>
        <li>En cas d'échec, la vitesse augmente.</li>
        <li>Un compte à rebours démarre avant le lancement.</li>
    </ul>
    <button onclick="startCountdown()">Commencer le jeu</button>
</div>

<h2>🎮 Lancer de couteau</h2>
<div id="countdown"></div>
<canvas id="gameCanvas" width="500" height="200"></canvas>
<div id="message"></div>
<div id="resultOverlay" style="
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0, 0, 0, 0.85);
    display: none;
    flex-direction: column; justify-content: center; align-items: center;
    color: white; font-size: 2rem; font-weight: bold;
    z-index: 1000;
    ">
    <div id="resultText" style="
        background: #e74c3c;
        padding: 30px 50px;
        border-radius: 20px;
        text-align: center;
        transform: scale(0.8);
        opacity: 0;
        transition: transform 0.8s ease, opacity 0.8s ease;
    ">Message</div>
</div>

<form id="resultForm" method="POST" action="resultat_attaque.php" style="display:none;">
    <input type="hidden" name="cible_id" value="<?= htmlspecialchars($cible_id) ?>">
    <input type="hidden" name="resultat" id="resultat">
</form>

<audio id="startSound" src="../assets/sounds/start.mp3"></audio>
<audio id="hitSound" src="../assets/sounds/hit.mp3"></audio>
<audio id="failSound" src="../assets/sounds/fail.mp3"></audio>

<script>
    console.log("Script chargé");
    const canvas = document.getElementById("gameCanvas");
    const ctx = canvas.getContext("2d");
    const countdownEl = document.getElementById("countdown");
    const overlay = document.getElementById("overlay");
    const startSound = document.getElementById("startSound");
    const hitSound = document.getElementById("hitSound");
    const failSound = document.getElementById("failSound");

    let target = { x: 0, y: 80, width: 60, height: 40, speed: 3 };
    let knife = { x: 220, y: 160, width: 60, height: 10 };
    let gameStarted = false;
    let gameOver = false;
    let speedFactor = 1;

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#e74c3c";
        ctx.fillRect(target.x, target.y, target.width, target.height);
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(knife.x, knife.y, knife.width, knife.height);
    }

    function update() {
        if (!gameOver && gameStarted) {
            target.x += target.speed * speedFactor;
            if (target.x + target.width >= canvas.width || target.x <= 0) {
                target.speed *= -1;
            }
        }
    }

    function gameLoop() {
        if (gameStarted) {
            update();
            draw();
            requestAnimationFrame(gameLoop);
        }
    }

    canvas.addEventListener("click", () => {
    if (!gameStarted || gameOver) return;

    const hitStart = target.x;
    const hitEnd = target.x + target.width;
    const knifeCenter = knife.x + knife.width / 2;

    const hit = knifeCenter >= hitStart && knifeCenter <= hitEnd;
    document.getElementById("resultat").value = hit ? "success" : "fail";

    gameOver = true;

    if (hit) {
        hitSound.currentTime = 0;
        hitSound.play().catch(e => console.warn("Erreur lecture hitSound:", e));
    } else {
        failSound.currentTime = 0;
        failSound.play().catch(e => console.warn("Erreur lecture failSound:", e));
        speedFactor += 0.5;
    }

    const overlay = document.getElementById("resultOverlay");
    const resultText = document.getElementById("resultText");
    overlay.style.display = "flex";

    if (hit) {
        resultText.innerText = "🎉 Succès ! Vous avez touché la cible !";
        resultText.style.backgroundColor = "#28a745"; // vert
    } else {
        resultText.innerText = "❌ Échec ! Vous avez raté la cible...";
        resultText.style.backgroundColor = "#e74c3c"; // rouge
    }

    resultText.style.transform = "scale(0.8)";
    resultText.style.opacity = "0";

    setTimeout(() => {
        resultText.style.transform = "scale(1)";
        resultText.style.opacity = "1";
    }, 50);

    setTimeout(() => {
        window.history.back(); // redirection vers page précédente
    }, 4000);
});


    function startCountdown() {
        console.log("Démarrage du compte à rebours");
        overlay.style.display = "none";
        startSound.currentTime = 0;
        startSound.play().catch(e => console.warn("Erreur lecture startSound:", e));

        let counter = 3;
        countdownEl.innerText = "🕒 " + counter;

        const interval = setInterval(() => {
            counter--;
            if (counter > 0) {
                countdownEl.innerText = "🕒 " + counter;
            } else {
                clearInterval(interval);
                countdownEl.innerText = "";
                gameStarted = true;
                gameLoop();
            }
        }, 1000);
    }
</script>
</body>
</html>
