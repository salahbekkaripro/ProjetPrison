<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';


if (!isset($_SESSION['ban_until']) && !isset($_SESSION['ban_permanent'])) {
    header('Location: ../index.php');
    exit;
}

$banPermanent = $_SESSION['ban_permanent'] ?? false;


$banUntil = strtotime($_SESSION['ban_until']);
$currentTime = time();
$remainingSeconds = max(0, $banUntil - $currentTime);
$customHeadStyle = <<<CSS
   body {
            background-color: black;
            color: #ff4d4d;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Orbitron', sans-serif;
            margin: 0;
            overflow: hidden;
            transition: opacity 1.5s ease-in-out;
        }
        body.fade-out {
            opacity: 0;
        }
        #ban-message {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 30px;
            white-space: pre-line;
        }
        #countdown {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: #ff6666;
        }
        #back-btn {
            background: transparent;
            border: 2px solid #ff4d4d;
            color: #ff4d4d;
            padding: 10px 20px;
            font-size: 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
        }
        #back-btn:hover {
            background: #ff4d4d;
            color: black;
        }
        /* Animation de vibration */
        @keyframes shake {
            0%, 100% { transform: translate(0, 0); }
            20% { transform: translate(-5px, 0); }
            40% { transform: translate(5px, 0); }
            60% { transform: translate(-5px, 0); }
            80% { transform: translate(5px, 0); }
        }
        body.shaking {
            animation: shake 0.5s;
            animation-iteration-count: 6; /* vibrer pendant 3s (0.5s * 6) */
        }
CSS;
?>

<!DOCTYPE html>
<html lang="fr">
    <?php include '../includes/head.php'; ?>
<body>

<h1>🚫 <?= $banPermanent ? "Vous êtes banni jusqu'à nouvel ordre." : "Vous êtes suspendu." ?></h1>

<?php if ($banPermanent): ?>
    <div style="font-size: 1.2rem; color: #ff6666; margin-bottom: 15px;">
        Type de bannissement : <strong>Permanent</strong> 🚫
    </div>
<?php else: ?>
    <div style="font-size: 1.2rem; color: #ffaa88; margin-bottom: 15px;">
        Durée du ban : 
        <?php
            $diff = $banUntil - $currentTime;
            $days = floor($diff / (60 * 60 * 24));
            $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
            $minutes = floor(($diff % (60 * 60)) / 60);
            echo "{$days} jours {$hours} heures {$minutes} minutes";
        ?>
    </div>
<?php endif; ?>


<div id="countdown"></div>

<audio id="fade-sound" src="assets/sounds/unlock.mp3" preload="auto"></audio>

<button id="back-btn" onclick="window.location.href='../index.php'">🏠 Retour à l'accueil</button>

<script>
const message = "🚫 Vous êtes suspendu.\nAccès refusé.";
const banMessage = document.getElementById('ban-message');
const fadeSound = document.getElementById('fade-sound');
let i = 0;

// Effet machine à écrire
function typeWriter(callback) {
    if (i < message.length) {
        banMessage.textContent += message.charAt(i);
        i++;
        setTimeout(() => typeWriter(callback), 60);
    } else {
        callback();
    }
}

// Compte à rebours
function startCountdown(seconds) {
    const countdown = document.getElementById('countdown');

    function updateCountdown() {
        if (seconds <= 0) {
            countdown.innerHTML = "🔓 Débannissement...";
            triggerFadeOut();
            return;
        }

        let h = Math.floor(seconds / 3600);
        let m = Math.floor((seconds % 3600) / 60);
        let s = seconds % 60;

        countdown.innerHTML = `⏳ Temps restant : ${h}h ${m}min ${s}s`;

        seconds--;
        setTimeout(updateCountdown, 1000);
    }

    updateCountdown();
}

// Fade out + redirection
function triggerFadeOut() {
    document.body.classList.add('fade-out');
    setTimeout(() => {
        window.location.href = '../index.php';
    }, 1500);
}

// 🎵 Jouer son et vibration
function playIntroSound() {
    fadeSound.play().catch(error => {
        console.log("Lecture audio bloquée par le navigateur :", error);
    });
    // Ajouter la vibration
    document.body.classList.add('shaking');
    // Retirer la vibration après 3 secondes
    setTimeout(() => {
        document.body.classList.remove('shaking');
    }, 3000);
}

// Démarrage de la page
window.onload = function() {
    playIntroSound(); // 🔥 jouer son + vibration au lancement
    typeWriter(() => {
        setTimeout(triggerFadeOut, 1500);
    });
    startCountdown(<?= $remainingSeconds ?>);
}
</script>

<?php include '../includes/footer.php'; ?>
