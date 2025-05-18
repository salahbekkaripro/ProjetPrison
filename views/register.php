<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $role = $_POST['role'] ?? '';
    $status = 'actif'; // par défaut

    if (empty($username) || empty($email) || empty($password) || empty($captcha) || empty($role) || empty($nom) || empty($prenom) || $age === 0) {
        $error = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif ($age < 18) {
        $error = "Vous devez avoir au moins 18 ans.";
    } elseif (!in_array($role, ['admin','prisonnier','gestionnaire'])) {
        $error = "Rôle dans la prison invalide.";
    } elseif (strtolower($captcha) !== strtolower($_SESSION['captcha_answer'] ?? '')) {
        $error = "Captcha incorrect. Essayez encore.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = "Email ou pseudo déjà utilisé.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("
                INSERT INTO users (username, email, password, created_at, role, status, age, nom, prenom)
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)
            ");
            $insert->execute([$username, $email, $hashed, $role, $status, $age, $nom, $prenom]);
            $userId = $pdo->lastInsertId();
            switch ($role) {
                case 'prisonnier':
                    $stmt = $pdo->prepare("INSERT INTO prisonnier (utilisateur_id, date_entree, cellule_id, etat)
                                           VALUES (?, CURDATE(), NULL, 'sain')");
                    $stmt->execute([$userId]);
                    break;
                case 'admin':
                    $stmt = $pdo->prepare("INSERT INTO admin (utilisateur_id, date_embauche, grade)
                                           VALUES (?, CURDATE(), 'stagiaire')");
                    $stmt->execute([$userId]);
                    break;
                case 'gestionnaire':
                    $stmt = $pdo->prepare("INSERT INTO gestionnaire (utilisateur_id, date_prise_fonction)
                                           VALUES (?, CURDATE())");
                    $stmt->execute([$userId]);
                    break;
            }
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                require_once '../includes/functions.php';
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'created_at' => $user['created_at'],
                    'avatar' => $user['avatar'] ?? null
                ];
                $_SESSION['just_logged_in'] = true;
                showOverlayRedirect("Bienvenue, " . $user["username"], "home.php", "Bienvenue, " . $user["username"]);
                exit;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<?php include('../includes/header.php'); ?>
<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
}
#app-content {
  height: 100%;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 20px;
}

@keyframes overlayZoomGlow {
  0% {
    transform: scale(1);
    text-shadow: 0 0 5px #ff0000, 0 0 10px #ff0000;
  }
  50% {
    transform: scale(1.02);
    text-shadow: 0 0 10px #ff1a1a, 0 0 20px #ff3300;
  }
  100% {
    transform: scale(1);
    text-shadow: 0 0 5px #ff0000, 0 0 10px #ff0000;
  }
}
#register-overlay.show {
  opacity: 1;
  pointer-events: auto;
  animation: overlayZoomGlow 2.5s ease-in-out;
}
#register-overlay.fade-out {
  opacity: 0;
  transition: opacity 0.1s ease-in-out;
}

#submit-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 165, 0, 0.2);
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 1em;
  cursor: not-allowed;
  width: 100%;
  transition: all 0.4s ease;
}
/* ⚡ Optimisation perf : on désactive les transitions coûteuses sur les inputs */
.form-input,
#submit-btn,
#pwd-criteria,
.pwd-criteria-box,
input,
button {
  transition: none !important;
  box-shadow: none !important;
  filter: none !important;
}
#submit-btn:enabled {
  cursor: pointer;
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}
#submit-btn:hover:enabled {
  background: rgba(255, 165, 0, 0.3);
}
.hidden { display: none; }
/* 🔥 Supprime les effets visuels coûteux pendant la saisie */
.form-input:focus,
#submit-btn,
.pwd-criteria-box,
#pwd-criteria,
input:focus,
button:focus {
  backdrop-filter: none !important;
  box-shadow: none !important;
  filter: none !important;
  animation: none !important;
  transition: none !important;
}
/* 🔧 Vitesse ultime sans perte visuelle */
* {
  will-change: auto !important;
  contain: layout style paint !important;
}
.form-input,
input,
button {
  will-change: transform;
  contain: content;
  isolation: isolate;
}
#app-content,
.form-container {
  will-change: auto;
  contain: strict;
}
.form-container {
  contain: layout style paint !important;
}
#app-content,
.form-container,
.form-input,
button,
input {
  contain: layout style paint;
}
.form-container {
  padding-bottom: 60px; /* ajoute de l'air en bas */
}
.link-subtle {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 165, 0, 0.2);
  padding: 8px 14px;
  color: #fff;
  text-decoration: none;
  font-size: 0.9rem;
  border-radius: 8px;
  transition: background 0.3s ease;
}
.link-subtle:hover {
  background: rgba(255, 165, 0, 0.15);
}
.form-container form {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}
#captcha-img {
  max-height: 48px;
}
#refresh-captcha {
  min-width: 40px;
}
@keyframes spin-refresh {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(180deg); } /* rotation plus douce */
}
.spin {
  display: inline-block;
  animation: spin-refresh 0.4s ease-in-out;
}

#refresh-captcha {
  background: transparent;
  border: none;
  padding: 6px;
  font-size: 1.5rem;
  cursor: pointer;
  color: white;
}
#refresh-captcha:hover {
  transform: scale(1.1);
}

html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
  background-color: #111;
}

#app {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

#app-content {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 40px; /* pour éviter que le bas soit coupé */
}

</style>
<body>
<a href="../index.php" class="link-button">◄ Page d'accueil</a>
<div id="page-transition"></div>
<div id="app">
  <div id="app-content">

<h2><div class="form-title">inscription.</div></h2>
<?php if ($error): ?>
  <div class="glass-box" style="color: #ff4d4d; padding: 10px; margin: 10px 0; animation: fadeIn 0.5s;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>
<div class="form-container register-container">
<form method="post" action="register.php" id="register-form">
  <label class="form-label">Nom</label>
  <input class="form-input" type="text" name="nom" required>

  <label class="form-label">Prénom</label>
  <input class="form-input" type="text" name="prenom" required>

  <label class="form-label">Âge</label>
  <input class="form-input" type="number" name="age" min="18" required>

  <label class="form-label">Rôle dans la prison</label>
  <select class="form-input" name="role" required>
    <option value="">-- Choisissez un rôle --</option>
    <option value="admin">Gardien ou Admin</option>
    <option value="prisonnier">Prisonnier</option>
    <option value="gestionnaire">Gestionnaire</option>
  </select>

  <label class="form-label">Pseudo</label>
  <input class="form-input" type="text" name="username" required autocomplete="username">
  <label class="form-label">Email</label>
  <input class="form-input" type="email" name="email" required autocomplete="email">
  <label class="form-label">Mot de passe</label>
  <input class="form-input" type="password" name="password" required autocomplete="new-password">
  <div id="pwd-criteria" class="pwd-criteria-box">
    <ul>
      <li id="crit-length">➤ 8 caractères minimum</li>
      <li id="crit-upper">➤ Une majuscule</li>
      <li id="crit-digit">➤ Un chiffre</li>
      <li id="crit-special">➤ Un caractère spécial</li>
    </ul>
    <div id="pwd-strength-msg" aria-live="polite">🔒 Niveau : —</div>
    </div>
  <label class="form-label">Confirmer le mot de passe</label>
  <input class="form-input" type="password" name="confirm" required autocomplete="new-password">
  <label class="form-label">Captcha</label>
  <input class="form-input" type="text" name="captcha" id="captcha" required inputmode="text">
  <div style="display: flex; align-items: center; gap: 12px; margin: 10px 0;">
  <img id="captcha-img" src="../actions/captcha_image.php" alt="Captcha" style="height: 50px; border-radius: 4px;">
  <button type="button" id="refresh-captcha">
  <span id="refresh-icon">⟳</span>
</button>
</div>
<span id="captcha-feedback"></span>

  <button type="submit" id="submit-btn" disabled>Créer mon compte</button>
</form>
<div style="text-align: center; margin-top: 25px; font-size: 0.95rem;">
  <span style="color: #ccc; margin-right: 5px;">Déjà inscrit ?</span>
  <a href="login.php" class="link-subtle">Se connecter</a>
</div> <!-- fin du div texte "déjà inscrit ?" -->


</div> <!-- .form-container -->

<!-- ✅ Script placé AVANT la fermeture de #app-content -->
<script id="register-script-content" type="text/template">
window.initRegisterPage = function () {
  let isCaptchaValid = false, isPasswordValid = false, isUsernameValid = false;

  function updateSubmitButtonState() {
    const btn = document.getElementById('submit-btn');
    if (btn) btn.disabled = !(isCaptchaValid && isPasswordValid && isUsernameValid);
  }

  function validateCaptchaInput() {
    const input = document.getElementById('captcha');
    const feedback = document.getElementById('captcha-feedback');
    if (!input || !feedback) return;
    fetch('../ajax/verify_captcha.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'captcha=' + encodeURIComponent(input.value)
    })
    .then(res => res.text())
    .then(text => {
      isCaptchaValid = text.trim().toLowerCase() === 'ok';
      feedback.textContent = isCaptchaValid ? "✔️ Captcha correct" : "❌ Captcha incorrect";
      feedback.style.color = isCaptchaValid ? 'lime' : 'red';
      feedback.style.opacity = 1;
      updateSubmitButtonState();
    });
  }

  const refreshBtn = document.getElementById('refresh-captcha');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => {
      const img = document.getElementById('captcha-img');
      if (img) img.src = '../actions/captcha_image.php?t=' + Date.now();
      const icon = document.getElementById('refresh-icon');
      if (icon) {
        icon.classList.add('spin');
        setTimeout(() => icon.classList.remove('spin'), 600);
      }
      const input = document.getElementById('captcha');
      const feedback = document.getElementById('captcha-feedback');
      if (input) input.value = '';
      if (feedback) feedback.textContent = '';
      isCaptchaValid = false;
      updateSubmitButtonState();
    });
  }

  const captchaInput = document.getElementById('captcha');
  if (captchaInput) captchaInput.addEventListener('input', validateCaptchaInput);
  validateCaptchaInput();

  const usernameInput = document.querySelector('input[name="username"]');
  if (usernameInput) {
    usernameInput.addEventListener('input', () => {
      isUsernameValid = usernameInput.value.trim().length > 0;
      updateSubmitButtonState();
    });
  }

  const pwdInput = document.querySelector('input[name="password"]');
  const strengthMsg = document.getElementById('pwd-strength-msg');
  const critLength = document.getElementById('crit-length');
  const critUpper = document.getElementById('crit-upper');
  const critDigit = document.getElementById('crit-digit');
  const critSpecial = document.getElementById('crit-special');
  const criteriaBox = document.getElementById('pwd-criteria');

  let debounceTimeout;
  if (pwdInput) {
    pwdInput.addEventListener('input', () => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        const pwd = pwdInput.value;
        const hasLength = pwd.length >= 8;
        const hasUpper = /[A-Z]/.test(pwd);
        const hasDigit = /\d/.test(pwd);
        const hasSpecial = /[^a-zA-Z0-9]/.test(pwd);
        if (critLength) critLength.style.display = hasLength ? 'none' : 'list-item';
        if (critUpper) critUpper.style.display = hasUpper ? 'none' : 'list-item';
        if (critDigit) critDigit.style.display = hasDigit ? 'none' : 'list-item';
        if (critSpecial) critSpecial.style.display = hasSpecial ? 'none' : 'list-item';
        const allGood = hasLength && hasUpper && hasDigit && hasSpecial;
        if (criteriaBox) criteriaBox.style.display = allGood ? 'none' : 'block';
        let level = "—", color = "#ccc";
        if (allGood) { level = "✅ Fort"; color = "lime"; }
        else if (pwd.length >= 6 && (hasUpper || hasDigit)) { level = "🟠 Moyen"; color = "orange"; }
        else if (pwd.length > 0) { level = "🔴 Faible"; color = "red"; }
        if (strengthMsg) {
          strengthMsg.textContent = "🔒 Niveau : " + level;
          strengthMsg.style.color = color;
        }
        isPasswordValid = allGood;
        updateSubmitButtonState();
      }, 100);
    });
  }

  const form = document.getElementById('register-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      if (!isCaptchaValid) {
        e.preventDefault();
        const feedback = document.getElementById('captcha-feedback');
        if (feedback) {
          feedback.textContent = "❌ Captcha incorrect.";
          feedback.style.color = "red";
          feedback.style.opacity = 1;
        }
      }
    });
  }

  isCaptchaValid = false;
  isPasswordValid = false;
  isUsernameValid = false;
  updateSubmitButtonState();
};
</script>

<?php unset($_SESSION['just_registered']); ?>
</div> <!-- #app-content -->
</div> <!-- #app -->
<div id="register-overlay"></div>

</body>
</html>