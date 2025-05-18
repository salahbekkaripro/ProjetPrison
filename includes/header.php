<?php require_once __DIR__ . '/head.php'; ?>
<?php
// Début du <body> et affichage de la vidéo de fond
?>
<body>

<div id="glow-border-overlay"></div>

<video autoplay loop muted playsinline preload="auto" id="background-video">
  <source src="/ProjetPrison/assets/videos/background.webm" type="video/webm">
  <source src="/ProjetPrison/assets/videos/background.mp4" type="video/mp4">
  Votre navigateur ne supporte pas les vidéos HTML5.
</video>

<div id="page-transition"></div>
<div id="app">
<script>
function updateNotifBadge() {
  fetch('/ProjetPrison/ajax/compteur_notifications.php')
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('notif-count');
      if (!badge) return;
      badge.textContent = data.unread;
      badge.style.display = data.unread > 0 ? 'inline-block' : 'none';
    });
}

function updateMsgCount() {
  fetch('/ProjetPrison/ajax/compteur_notifications.php')
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('msg-count');
      if (!badge) return;
      badge.textContent = data.unread;
      badge.style.display = data.unread > 0 ? 'inline-block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', () => {
  updateNotifBadge();
  updateMsgCount();
  setInterval(updateNotifBadge, 5000);
  setInterval(updateMsgCount, 5000);
});
</script>
