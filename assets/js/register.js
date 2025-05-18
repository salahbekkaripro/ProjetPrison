let isCaptchaValid = false, isPasswordValid = false, isUsernameValid = false;
function updateSubmitButtonState() {
  document.getElementById('submit-btn').disabled = !(isCaptchaValid && isPasswordValid && isUsernameValid);
}

// Réinitialise les flags à chaque chargement complet
window.addEventListener('load', () => {
  isCaptchaValid = false;
  isPasswordValid = false;
  isUsernameValid = false;
  updateSubmitButtonState();
});

document.addEventListener('DOMContentLoaded', () => {

  function validateCaptchaInput() {
    const input = document.getElementById('captcha');
    const feedback = document.getElementById('captcha-feedback');
    fetch('verify_captcha.php', {
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

  document.getElementById('refresh-captcha').addEventListener('click', () => {
    const img = document.getElementById('captcha-img');
    img.src = 'captcha_image.php?t=' + Date.now();
    const icon = document.getElementById('refresh-icon');
    icon.classList.add('spin');
    setTimeout(() => icon.classList.remove('spin'), 600);
    document.getElementById('captcha').value = '';
    document.getElementById('captcha-feedback').textContent = '';
    isCaptchaValid = false;
    updateSubmitButtonState();
  });

  document.getElementById('captcha').addEventListener('input', validateCaptchaInput);
  validateCaptchaInput();

  const usernameInput = document.querySelector('input[name="username"]');
  usernameInput.addEventListener('input', () => {
    isUsernameValid = usernameInput.value.trim().length > 0;
    updateSubmitButtonState();
  });

  const pwdInput = document.querySelector('input[name="password"]');
  const strengthMsg = document.getElementById('pwd-strength-msg');
  const critLength = document.getElementById('crit-length');
  const critUpper = document.getElementById('crit-upper');
  const critDigit = document.getElementById('crit-digit');
  const critSpecial = document.getElementById('crit-special');
  const criteriaBox = document.getElementById('pwd-criteria');

  let debounceTimeout;
  pwdInput.addEventListener('input', () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      const pwd = pwdInput.value;
      const hasLength = pwd.length >= 8;
      const hasUpper = /[A-Z]/.test(pwd);
      const hasDigit = /\d/.test(pwd);
      const hasSpecial = /[^a-zA-Z0-9]/.test(pwd);
      critLength.style.display = hasLength ? 'none' : 'list-item';
      critUpper.style.display = hasUpper ? 'none' : 'list-item';
      critDigit.style.display = hasDigit ? 'none' : 'list-item';
      critSpecial.style.display = hasSpecial ? 'none' : 'list-item';
      const allGood = hasLength && hasUpper && hasDigit && hasSpecial;
      const targetDisplay = allGood ? 'none' : 'block';
      if (criteriaBox.style.display !== targetDisplay) {
        criteriaBox.style.display = targetDisplay;
      }
      let level = "—", color = "#ccc";
      if (allGood) { level = "✅ Fort"; color = "lime"; }
      else if (pwd.length >= 6 && (hasUpper || hasDigit)) { level = "🟠 Moyen"; color = "orange"; }
      else if (pwd.length > 0) { level = "🔴 Faible"; color = "red"; }
      if (strengthMsg.textContent !== "🔒 Niveau : " + level) {
        strengthMsg.textContent = "🔒 Niveau : " + level;
        if (strengthMsg.style.color !== color) {
          strengthMsg.style.color = color;
        }
      }
      isPasswordValid = allGood;
      updateSubmitButtonState();
    }, 100);
  });

  document.getElementById('register-form').addEventListener('submit', function(e) {
    if (!isCaptchaValid) {
      e.preventDefault();
      const feedback = document.getElementById('captcha-feedback');
      feedback.textContent = "❌ Captcha incorrect.";
      feedback.style.color = "red";
      feedback.style.opacity = 1;
    }
  });
});
