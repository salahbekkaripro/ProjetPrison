<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  session_start();
  require_once '../includes/db.php';
  require_once '../includes/functions.php';
  require_once '../includes/header.php';
  if (isset($_SESSION['user'])) {
    header('Location: /ProjetPrison/views/home.php');  // 🔁 Change 'home.php' si besoin
    exit;
}

  $identifier = trim($_POST['identifier'] ?? '');
  $password = $_POST['password'] ?? '';
  $captcha = $_POST['captcha'] ?? '';

  if (empty($identifier) || empty($password) || empty($captcha)) {
      $error = "Tous les champs sont requis.";
  } elseif (strtolower($captcha) !== strtolower($_SESSION['captcha_answer'] ?? '')) {
    $_SESSION['ban_message'] = $banMessage;
    header('Location: ../admin/ban_notice.php');
    exit;
    
  } else {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
      $stmt->execute([$identifier, $identifier]);
      $user = $stmt->fetch();

      if ($user && password_verify($password, $user['password'])) {
        // 🛑 Vérification bannissement
        if ($user['is_banned']) {
            $_SESSION['captcha_refresh'] = true;
    
            if ($user['ban_until']) {
                $_SESSION['ban_until'] = $user['ban_until'];
                $_SESSION['ban_permanent'] = false;
            } else {
                $_SESSION['ban_permanent'] = true;
            }
    
            header('Location: ../admin/ban_notice.php');
            exit;
        }
    // Stockage complet dans la session, en ajoutant nom et prenom
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'created_at' => $user['created_at'],
        'avatar' => $user['avatar'] ?? null,
        'nom' => $user['nom'] ?? '',
        'prenom' => $user['prenom'] ?? ''
    ];

        if ($user['role'] === 'admin') {
            $_SESSION['admin'] = true;
        }
    
        $_SESSION['just_logged_in'] = true;
        showOverlayRedirect("Bienvenue, " . $user['username'], "home.php");
        exit;
    }
    
     else {
          $error = "Identifiants invalides.";
      }
  }
}


if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/db.php';

require_once '../includes/functions.php';
require_once '../includes/header.php';


$error = $error ?? '';

$pageTitle = "Connexion.";

$customHeadStyle = <<<CSS

#login-overlay {
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
  backdrop-filter: blur(2px); /* effet mat discret */
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

#login-overlay.show {
  opacity: 1;
  pointer-events: auto;
  animation: overlayZoomGlow 3s ease-in-out infinite;
}

#login-overlay.fade-out {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
}


#admin-warning {
  display: none;
  background: #220000;
  color: #ff6666;
  border: 1px solid #ff0000;
  padding: 10px;
  margin-top: 10px;
  font-family: 'Orbitron', sans-serif;
  font-size: 0.9em;
  animation: fadeIn 0.5s;
}
form.form-input button {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 165, 0, 0.2);
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 1em;
    cursor: pointer;
    width: 100%;
    transition: all 0.4s ease;
    backdrop-filter: blur(5px);
    margin-top: 10px;
}
form.form-input button:hover {
    background: rgba(155, 149, 140, 0.3);
    box-shadow: 0 0 10px rgba(131, 127, 120, 0.5);
}
#captcha-error {
    color: #ff6666;
    font-size: 0.9em;
    margin-top: 5px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#captcha-error.show {
    opacity: 1;
}

body {
  background-color: white; /* ou un gris très clair si tu veux */
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
  backdrop-filter: blur(2px); /* effet mat discret */
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

#logout-overlay.fade-out {
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
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

CSS;
    
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>

<body>
<div id="logout-overlay"></div>
<div id="flash-overlay"></div> <!-- 👈 Flash blanc superposé -->

<a href="../index.php" class="link-button">◄ Page d'accueil</a>
<div id="page-transition"></div>
<div id="app-content">
    <h2><div class="form-title">CONNEXION.</div></h2>

    <?php if ($error): ?>
      <div class="glass-box error-box">
    <?= htmlspecialchars($error) ?>
</div>

    <?php endif; ?>

    <div class="form-container">
  <form method="post" id="login-form" class="form-input">
    <input type="text" name="identifier" id="identifier" placeholder="Nom d'utilisateur ou email" required><br><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br><br>
    
    <div class="form-input" style="margin: 10px 0; padding: 10px; text-align: center;">
    <img id="captcha-image" src="/ProjetPrison/actions/captcha_image.php" alt="Captcha" style="margin-bottom: 10px; border-radius: 8px; border: 1px solid rgba(255,165,0,0.5); box-shadow: 0 0 8px rgba(255,165,0,0.4);">
    <input type="text" name="captcha" placeholder="Recopiez le texte du captcha" required style="margin-top: 8px; width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: none; border-radius: 10px; color: white; font-size: 1em;">
    <div id="captcha-error"></div>
    </div>


    <button type="submit" id="login-submit">Se connecter</button>

    <div id="admin-warning">⚠️ Toute tentative de connexion sur un compte admin de la part d’un détenu engendrera de lourdes sanctions.</div>
</form>
</div>


<div class="register-center">
  <p>Nouveau ici ?</p>
  <a href="register.php" class="link-button">Inscrivez-vous</a>
</div>

</div>

<audio id="login-success-sound" src="/ProjetPrison/assets/sounds/login.mp3" preload="auto"></audio>
<audio id="admin-warning-sound" src="/ProjetPrison/assets/sounds/warning.mp3" preload="auto"></audio>
<div id="login-overlay">🔐 Ne troublez pas l'ordre...</div>

<?php if (!empty($_SESSION['just_logged_in']) && !empty($_SESSION['user']['username'])): ?>
    <script>
window.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('logout-overlay');
  const audio = document.getElementById('login-success-sound');
  const text = "Déconnexion en cours...";
  let i = 0;
  overlay.textContent = '';
  overlay.classList.add('show');
  audio.play();

  const flash = document.getElementById('flash-overlay');

const typewriter = setInterval(() => {
  if (i < text.length) {
    overlay.textContent += text[i];
    i++;
  } else {
    clearInterval(typewriter);

    // 🕐 Attendre 1s après texte fini
    setTimeout(() => {
  overlay.remove();
  flash.classList.add('show');

  setTimeout(() => {
    window.location.href = '/ProjetPrison/index.php';
  }, 700); // redirection pendant le flash encore actif
}, 1000);



  }
}, 100);



});
</script>

<?php unset($_SESSION['just_logged_in']); endif; ?>

<script>
const identifierInput = document.getElementById('identifier');
const warningBox = document.getElementById('admin-warning');
const warningSound = document.getElementById('admin-warning-sound');

identifierInput.addEventListener('input', function () {
    if (this.value.trim().toLowerCase() === 'admin') {
        warningBox.style.display = 'block';
        warningSound.play();
    } else {
        warningBox.style.display = 'none';
    }
});
</script>

<script>
// Rafraîchir l'image captcha si besoin
window.addEventListener('DOMContentLoaded', () => {
    <?php if (!empty($_SESSION['captcha_refresh'])): ?>
    const captchaImage = document.getElementById('captcha-image');
    if (captchaImage) {
        const timestamp = new Date().getTime();
        captchaImage.src = '/ProjetPrison/actions/captcha_image.php?t=' + timestamp;
    }
    <?php unset($_SESSION['captcha_refresh']); endif; ?>
});
</script>



<?php include '../includes/footer.php'; ?>
<script>
document.getElementById('login-submit').addEventListener('click', () => {
    const audio = document.getElementById('login-success-sound');
    if (audio) {
        audio.play().catch(err => console.warn("Audio bloqué ou erreur :", err));
    }
});
</script>

</body>
</html>