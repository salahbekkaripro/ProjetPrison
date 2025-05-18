<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
$pageTitle = "Accueil - Forum des prisonniers";
if (isset($_SESSION['user'])) {
    header('Location: /ProjetPrison/views/home.php');  // 🔁 Change 'home.php' si besoin
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>

<body>
<section class="slider-scene">
  <div class="parallax-layer layer-quote">
    <blockquote id="quote" class="animated-quote"></blockquote>
    <div class="quote-controls" style="text-align: center; margin-top: 20px;">
      <button id="prev-quote" class="quote-btn">&lt;</button>
      <span id="quote-count">1 / 8</span>
      <button id="next-quote" class="quote-btn">&gt;</button>
    </div>
  </div>
</section>

<div style="text-align:center; margin-top:30px;">
  <a href="/ProjetPrison/views/login.php" class="sort-btn">Connexion</a>
  <a href="/ProjetPrison/views/register.php" class="sort-btn">S'inscrire</a>
    <a href="/ProjetPrison/views/home.php" class="sort-btn">Voir l'espace de discussions</a>

</div>


  <div class="about-box">
    <h2>à propos</h2>
    <p>Ce forum est un espace libre de discussions pour les personnes incarcérées ou isolées.
       Ici, la parole circule, les histoires se racontent, les soutiens se construisent.
       Anonymat garanti.</p>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>