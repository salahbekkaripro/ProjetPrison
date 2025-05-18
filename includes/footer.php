<!-- Ton footer visuel -->
<footer>
    <p>&copy; <?= date('Y') ?> - Forum des prisonniers. Tous droits réservés.</p>
</footer>

<!-- Glow futuriste auto-disparaissant -->
<script>
window.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('glow-border-overlay');
  if (overlay) {
    setTimeout(() => {
      overlay.style.transition = 'opacity 1s ease';
      overlay.style.opacity = '0';
    }, 2500);
  }
});
</script>

</div> <!-- Fin de #app -->
</body>
</html>
