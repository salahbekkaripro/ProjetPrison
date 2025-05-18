<?php
session_start();

$user = $_SESSION['user'] ?? null;
$username = $user['username'] ?? 'utilisateur';
$role = $user['role'] ?? 'user';

$redirect = $_SESSION['overlay_redirect'] ?? 'home.php';
unset($_SESSION['overlay_redirect']);

$message = $_SESSION['overlay_force_message'] ?? " ";
unset($_SESSION['overlay_force_message']);


$sound = $_SESSION['overlay_sound'] ?? 'login.mp3';
unset($_SESSION['overlay_sound']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Confirmation de redirection</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      background: linear-gradient(145deg, #2a2a2a, #1c1c1c, #2f2f2f, #1a1a1a);
      color: #f8f8f8;
      font-family: 'Rajdhani', sans-serif;
      font-size: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      overflow: hidden;
      flex-direction: column;
      text-align: center;
    }

    .question-box {
      max-width: 600px;
      padding: 20px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      margin-bottom: 40px;
      box-shadow: 0 0 20px rgba(255,255,255,0.05);
    }

    .btn-choice {
      background-color: #333;
      color: white;
      border: none;
      padding: 15px 25px;
      margin: 10px;
      font-size: 1rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .btn-choice:hover {
      background-color: #555;
    }

    #overlay-text {
      animation: overlayZoomGlow 3s ease-in-out infinite;
      font-size: 2rem;
      margin-top: 40px;
    }

    @keyframes overlayZoomGlow {
      0% { transform: scale(1); text-shadow: 0 0 4px #ccc, 0 0 10px #eee; }
      50% { transform: scale(1.03); text-shadow: 0 0 12px #fff, 0 0 24px #ddd; }
      100% { transform: scale(1); text-shadow: 0 0 4px #ccc, 0 0 10px #eee; }
    }

    #flash {
      position: fixed;
      inset: 0;
      background: white;
      opacity: 0;
      z-index: 10000;
      pointer-events: none;
    }

    #flash.show {
      animation: flashFadeInOut 1.5s ease forwards;
    }

    @keyframes flashFadeInOut {
      0% { opacity: 0; }
      30% { opacity: 1; }
      70% { opacity: 1; }
      100% { opacity: 0; }
    }
  </style>
</head>
<body>

<div id="question-part" class="question-box">
  <p>🤔 Êtes-vous vraiment prêt à entrer dans le pénitencier numérique ?</p>
  <button class="btn-choice" onclick="beginOverlay()">Oui, j’assume mes actes 😔</button>
  <button class="btn-choice" onclick="stayFree()">Je prends mes responsabilités, au boulot 👨‍🏭</button>
</div>

<div id="overlay-text" style="display:none;"></div>
<div id="flash"></div>

<audio id="sound" preload="auto"></audio>

<script>
const userRole = <?= json_encode($role) ?>;
const userName = <?= json_encode($username) ?>;
const text = <?= json_encode($message) ?>;
const redirectUrl = <?= json_encode($redirect) ?>;
const overlay = document.getElementById('overlay-text');
const flash = document.getElementById('flash');
const sound = document.getElementById('sound');

function beginOverlay() {
  document.getElementById('question-part').style.display = 'none';
  overlay.style.display = 'block';

  let preMessage = '';
  let chosenSound = 'login.mp3';

  if (userRole === 'admin') {
    preMessage = `🚨 HOP HOP TU VAS OÙ ${userName.toUpperCase()} ? VA BOSSER !\n`;
    chosenSound = 'alarme.mp3';
  }

  if (userRole === 'prisonnier') {
    preMessage = `🔐 Ne troublez pas l'ordre...${userName.toUpperCase()}\n`;
  }

  if (userRole === 'gestionnaire') {
    preMessage = `🚨 HOP HOP TU VAS OÙ ${userName.toUpperCase()}?? VA BOSSER !\n`;
    chosenSound = 'alarme.mp3';
  }

  sound.src = `/ProjetPrison/assets/sounds/${chosenSound}`;

  let i = 0;
  overlay.textContent = '';
  const fullText = preMessage + text;

  sound.play().catch(err => console.warn("🔇 Son bloqué :", err));

  const typewriter = setInterval(() => {
    if (i < fullText.length) {
      overlay.textContent += fullText[i];
      i++;
    } else {
      clearInterval(typewriter);
      setTimeout(() => {
        flash.classList.add('show');
        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 700);
      }, 1000);
    }
  }, 100);
}


function stayFree() {
  document.getElementById('question-part').style.display = 'none';
  overlay.style.display = 'block';

  let preMessage = '';
  let chosenSound = 'login.mp3';

  if (userRole === 'prisonnier') {
    preMessage = `🚨 HOP HOP TU VAS OÙ ${userName.toUpperCase()} ? TOI T’ES PRISONNIER !\n`;
    chosenSound = 'alarme.mp3';
  }

  if (userRole === 'admin') {
    preMessage = `🛡️ Faites régner l'ordre... ${userName.toUpperCase()}  !\n`;
  }

  if (userRole === 'gestionnaire') {
    preMessage = `📦 Gérez l'ordre logistique, chef  ${userName.toUpperCase()}.\n`;
    }

  sound.src = `/ProjetPrison/assets/sounds/${chosenSound}`;

  let i = 0;
  overlay.textContent = '';
  const fullText = preMessage + text;

  sound.play().catch(err => console.warn("🔇 Son bloqué :", err));

  const typewriter = setInterval(() => {
    if (i < fullText.length) {
      overlay.textContent += fullText[i];
      i++;
    } else {
      clearInterval(typewriter);
      setTimeout(() => {
        flash.classList.add('show');
        setTimeout(() => {
          if (userRole === 'prisonnier') {
            window.location.href = '/ProjetPrison/views/home.php';
          } else {
            window.location.href = redirectUrl;
          }
        }, 700);
      }, 1000);
    }
  }, 100);
}


</script>
</body>
</html>
