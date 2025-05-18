<?php
require_once '../includes/header.php';
$customHeadStyle = <<<CSS
/* Styles identiques à ta version actuelle */
body {
  background-color: white;
  transition: background 0.5s ease;
}
#logout-overlay {
  position: fixed;
  inset: 0;
  background: linear-gradient(145deg, #2a2a2a, #1c1c1c, #2f2f2f, #1a1a1a);
  background-blend-mode: multiply;
  color: #f8f8f8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Orbitron', sans-serif;
  font-size: 2rem;
  z-index: 9999;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.8s ease-in-out;
  text-align: center;
  backdrop-filter: blur(2px);
}

@keyframes overlayZoomGlow {
  0% {
    transform: scale(1);
    text-shadow: 0 0 4px #cccccc, 0 0 10px #eeeeee;
  }
  50% {
    transform: scale(1.03);
    text-shadow: 0 0 12px #ffffff, 0 0 24px #dddddd;
  }
  100% {
    transform: scale(1);
    text-shadow: 0 0 4px #cccccc, 0 0 10px #eeeeee;
  }
}

#logout-overlay.show {
  opacity: 1;
  pointer-events: auto;
  animation: overlayZoomGlow 3s ease-in-out infinite;
}

@keyframes flashFadeInOut {
  0% { opacity: 0; }
  30% { opacity: 1; }
  70% { opacity: 1; }
  100% { opacity: 0; }
}

#flash-overlay {
  position: fixed;
  inset: 0;
  background: white;
  opacity: 0;
  z-index: 10000;
  pointer-events: none;
}

#flash-overlay.show {
  animation: flashFadeInOut 1.5s ease forwards;
}

.logout-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
.logout-btn {
  font-size: 1.5rem;
  padding: 12px 24px;
  border: none;
  background-color: #222;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Orbitron', sans-serif;
  transition: background 0.3s ease;
}
.logout-btn:hover {
  background-color: #444;
}

/* MODALE */
#confirmModal {
  position: fixed;
  z-index: 10001;
  inset: 0;
  background: rgba(10, 10, 10, 0.85) url('../assets/images/prison_bars.png') center/cover;
  display: flex;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(3px);
}

#confirmModal .modal-box {
  background: linear-gradient(145deg, #2c2c2c, #1a1a1a);
  color: #f1f1f1;
  padding: 30px;
  border-radius: 12px;
  text-align: center;
  max-width: 450px;
  font-family: 'Orbitron', sans-serif;
  box-shadow: 0 0 20px rgba(255,255,255,0.05);
  border: 2px solid #555;
  animation: fadeInModal 0.7s ease-in-out;
}

#confirmModal .modal-box h2 {
  font-size: 1.6rem;
  margin-bottom: 20px;
  line-height: 1.5;
}

#confirmModal .modal-box::before {
  content: "🔒";
  font-size: 2.5rem;
  display: block;
  margin-bottom: 10px;
  animation: pulseIcon 1s infinite alternate;
}

#confirmModal button {
  margin: 10px;
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
  letter-spacing: 0.5px;
  font-size: 1rem;
  box-shadow: 0 0 5px rgba(0,0,0,0.3);
}

#confirmModal .yes {
  background-color: #d9534f;
  color: white;
}

#confirmModal .no {
  background-color: #5bc0de;
  color: white;
}

@keyframes fadeInModal {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes pulseIcon {
  from { transform: scale(1); opacity: 0.8; }
  to { transform: scale(1.1); opacity: 1; }
}

CSS;
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<body>
<!-- Overlays -->
<div id="logout-overlay"></div>
<div id="flash-overlay"></div>
<audio id="logout-sound" src="../assets/sounds/logout.mp3" preload="auto"></audio>

<!-- Modale confirmation -->
<div id="confirmModal">
  <div class="modal-box">
    <h2>🔒 Dernier avertissement !<br>Vous êtes sur le point de quitter le pénitencier numérique.</h2>
    <p>Oui, vous avez bien raison de fuir ce lieu... ou peut-être préférez-vous rester un peu plus ?</p>
    <button class="yes">Oui, je quitte cette prison 🏃</button>
    <button class="no">Non, je reste enfermé 🔐</button>
  </div>
</div>


<script>
const confirmModal = document.getElementById('confirmModal');
const overlay = document.getElementById('logout-overlay');
const flash = document.getElementById('flash-overlay');
const audio = document.getElementById('logout-sound');

confirmModal.querySelector('.yes').addEventListener('click', () => {
    confirmModal.style.display = 'none';
    const text = "Déconnexion en cours...";
    let i = 0;
    overlay.textContent = '';
    overlay.classList.add('show');

    audio.play().catch(err => {
        console.warn("Lecture audio bloquée :", err);
    });

    const typewriter = setInterval(() => {
        if (i < text.length) {
            overlay.textContent += text[i];
            i++;
        } else {
            clearInterval(typewriter);

            setTimeout(() => {
                overlay.remove();
                flash.classList.add('show');

                setTimeout(() => {
                    // 🔁 Redirection vers un script qui fait la destruction de session
                    window.location.href = 'final_logout.php';
                }, 700);
            }, 1000);
        }
    }, 100);
});

confirmModal.querySelector('.no').addEventListener('click', () => {
    // ❌ Annule la déconnexion → redirection accueil ou page précédente
    window.location.href = '../views/home.php';
});
</script>
</body>
</html>
