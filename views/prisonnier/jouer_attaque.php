<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';

// Vérifie que seul le rôle 'prisonnier' peut accéder
checkRole('prisonnier');
require_user_login();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    exit("⛔ Accès interdit.");
}
$objet_id = $_POST['objet_id'] ?? null;
$cible_id = $_POST['cible_id'] ?? $_SESSION['attaque']['cible_id'] ?? null;
if ($cible_id) {
    $_SESSION['attaque']['cible_id'] = $cible_id;
} else {
    exit("❌ Données d'attaque manquantes.");
}
$user_id = $_SESSION['user']['id'];
$prisonnier_id = $_SESSION['user']['prisonnier_id'] ?? null;  // <--- ici

// Supprimer l'objet utilisé de l'inventaire
if ($objet_id && $prisonnier_id) {
    // Vérifier que l'objet appartient bien au prisonnier
    $stmt = $pdo->prepare("SELECT id FROM objets_prisonniers WHERE id = ? AND prisonnier_id = ?");
    $stmt->execute([$objet_id, $prisonnier_id]);
    $objet = $stmt->fetch();

    if ($objet) {
        // Supprimer l'objet (perte)
        $delStmt = $pdo->prepare("DELETE FROM objets_prisonniers WHERE id = ?");
        $delStmt->execute([$objet_id]);
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>🎯 Mini-jeu de lancer de couteau</title>
    <style>
        body { background: #1b1b1b; color: white; font-family: 'Segoe UI', sans-serif; text-align: center; margin:0; padding:0; }
        canvas {
            margin-top: 30px;
            border: 5px solid #e74c3c;
            border-radius: 10px;
            background-color: #2c2c2c;
            display: block;
            margin-left:auto;
            margin-right:auto;
            position: relative;
        }
        #message {
            margin-top: 20px;
            font-size: 1.8rem;
            font-weight: bold;
            user-select: none;
        }
        #overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.95);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            z-index: 999;
            padding: 20px;
        }
        #overlay h2 { font-size: 2.5rem; margin-bottom: 20px; }
        #overlay ul { text-align: left; max-width: 600px; margin: 0 20px; font-size: 1.2rem; line-height: 1.5; }
        #overlay button {
            margin-top: 25px; padding: 14px 28px; background-color: #28a745;
            border: none; border-radius: 8px; font-size: 1.3rem; font-weight: bold;
            cursor: pointer;
            box-shadow: 0 0 12px #28a745aa;
            transition: background-color 0.3s ease;
        }
        #overlay button:hover {
            background-color: #1e7e34;
        }
        #countdown {
            font-size: 2.5rem;
            margin-top: 20px;
            color: #ffcc00;
            font-weight: bold;
            user-select: none;
        }
        /* Animation gouttes de sang */
        .blood-drop {
            position: absolute;
            width: 10px;
            height: 10px;
            background: radial-gradient(circle at center, #c0392b 0%, #7b1a1a 70%);
            border-radius: 50%;
            opacity: 0.8;
            animation: fallBlood 1.2s ease forwards;
            pointer-events: none;
            z-index: 1000;
        }
        @keyframes fallBlood {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0.8;
            }
            100% {
                transform: translateY(80px) scale(0.3);
                opacity: 0;
            }
        }
        /* Cible qui pulse en cas de réussite */
        .target-hit {
            animation: pulseRed 0.8s ease forwards;
        }
        @keyframes pulseRed {
            0% { box-shadow: 0 0 10px 2px #e74c3c; }
            50% { box-shadow: 0 0 25px 6px #ff0000; }
            100% { box-shadow: 0 0 10px 2px #e74c3c; }
        }
        /* Style select dans overlay */
        #objet-select {
            padding: 8px;
            width: 100%;
            max-width: 300px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 1rem;
            border: none;
        }
        #objet-select:focus {
            outline: 2px solid #28a745;
        }
        label {
            color: white;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
    </style>
</head>
<body>

<div id="overlay">
    <h2>🔪 Mini-jeu : Lancer de couteau</h2>
    <ul>
        <li>Un couteau est prêt à être lancé au centre.</li>
        <li>Une cible rouge se déplace de gauche à droite.</li>
        <li>Cliquez lorsque le couteau est aligné avec la cible.</li>
        <li>Une seule chance par attaque.</li>
        <li>En cas d’échec, la vitesse de la cible augmente.</li>
        <li>Un compte à rebours démarre avant le lancement.</li>
    </ul>

    <label for="objet-select">Choisissez un objet interdit à utiliser pour l'attaque :</label>
    <select id="objet-select">
        <option value="">-- Choisissez un objet --</option>
        <?php
        $stmtObj = $pdo->prepare("
            SELECT op.id, od.nom
            FROM objets_prisonniers op
            JOIN objets_disponibles od ON od.id = op.objet_id
            WHERE op.prisonnier_id = (SELECT prisonnier_id FROM prisonnier WHERE utilisateur_id = ?)
            AND od.interdit = 1
        ");
        $stmtObj->execute([$_SESSION['user']['id']]);
        $objetsInterdits = $stmtObj->fetchAll();
        foreach ($objetsInterdits as $oi) {
            echo '<option value="' . intval($oi['id']) . '">' . htmlspecialchars($oi['nom']) . '</option>';
        }
        ?>
    </select>

    <button onclick="startCountdown()" id="start-button">Commencer le jeu</button>
</div>

<h2>🎮 Lancer de couteau</h2>
<div id="countdown"></div>
<canvas id="gameCanvas" width="500" height="200"></canvas>
<div id="message"></div>

<form id="resultForm" method="POST" action="resultat_attaque.php" style="display:none;">
    <input type="hidden" name="cible_id" value="<?= htmlspecialchars($cible_id) ?>">
    <input type="hidden" name="resultat" id="resultat">
    <input type="hidden" name="objet_id" id="resultat-form-objet-id" value="">
</form>

<audio id="startSound" src="../../assets/sounds/start.mp3"></audio>
<audio id="hitSound" src="../../assets/sounds/hit.mp3"></audio>
<audio id="failSound" src="../../assets/sounds/fail.mp3"></audio>

<script>
    const canvas = document.getElementById("gameCanvas");
    const ctx = canvas.getContext("2d");
    const countdownEl = document.getElementById("countdown");
    const overlay = document.getElementById("overlay");
    const messageEl = document.getElementById("message");

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

    function createBloodDrop(x, y) {
        const drop = document.createElement('div');
        drop.className = 'blood-drop';
        drop.style.left = x + 'px';
        drop.style.top = y + 'px';
        document.body.appendChild(drop);

        drop.addEventListener('animationend', () => {
            drop.remove();
        });
    }

    canvas.addEventListener("click", () => {
        if (!gameStarted || gameOver) return;

        const hitStart = target.x;
        const hitEnd = target.x + target.width;
        const knifeCenter = knife.x + knife.width / 2;

        const hit = knifeCenter >= hitStart && knifeCenter <= hitEnd;
        document.getElementById("resultat").value = hit ? "success" : "fail";

        if (hit) {
            messageEl.innerHTML = "🎯 Succès ! Cible touchée avec le 🔪";
            canvas.classList.add('target-hit');
            hitSound.currentTime = 0;
            hitSound.play().catch(e => console.warn("Erreur hitSound:", e));
        } else {
            messageEl.innerHTML = "❌ Échec ! Le couteau a raté la cible...";
            failSound.currentTime = 0;
            failSound.play().catch(e => console.warn("Erreur failSound:", e));
            speedFactor += 0.5;
            for(let i=0; i<8; i++) {
                const randX = canvas.offsetLeft + Math.random() * canvas.width;
                const randY = canvas.offsetTop + Math.random() * canvas.height;
                createBloodDrop(randX, randY);
            }
        }

        gameOver = true;
        setTimeout(() => {
            canvas.classList.remove('target-hit');
            document.getElementById("resultForm").submit();
        }, 2500);
    });

    function startCountdown() {
        const selectObj = document.getElementById('objet-select');
        const selectedObjetId = selectObj.value;
        if (!selectedObjetId) {
            alert("⚠️ Vous devez choisir un objet interdit avant de commencer !");
            return;
        }

        document.getElementById('resultat-form-objet-id').value = selectedObjetId;

        overlay.style.display = "none";
        startSound.currentTime = 0;
        startSound.play().catch(e => console.warn("Erreur startSound:", e));

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

    gameLoop();
</script>
</body>
</html>