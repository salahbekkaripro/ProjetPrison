<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'db.php';

// Vérifie que c'est bien un prisonnier connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prisonnier') return;

// Probabilité de génération (5% → 1 chance sur 20)
$chance = 5;
if (rand(1, 100) > $chance) return;

// Liste des objets potentiels
$objets = [
    ['nom' => 'Couteau', 'description' => 'Un petit couteau rouillé', 'interdit' => 1],
    ['nom' => 'Savon', 'description' => 'Un savon glissant', 'interdit' => 0],
    ['nom' => 'Cuillère', 'description' => 'Cuillère en plastique', 'interdit' => 1],
    ['nom' => 'Miroir', 'description' => 'Petit miroir cassé', 'interdit' => 1],
    ['nom' => 'Livre', 'description' => 'Un vieux livre de droit', 'interdit' => 0]
];

// Sélection d’un objet aléatoire
$objet = $objets[array_rand($objets)];

// Création du lien de récupération
$recup_url = "../controllers/recuperer_objet.php?nom=" . urlencode($objet['nom']) .
             "&description=" . urlencode($objet['description']) .
             "&interdit=" . $objet['interdit'];

// Position aléatoire sur l'écran
$top = rand(10, 80);   // % du haut
$left = rand(5, 90);   // % de la gauche

echo <<<HTML
<a href="$recup_url" style="
    position: fixed;
    top: {$top}%;
    left: {$left}%;
    z-index: 1000;
    background-color: rgba(255,0,0,0.8);
    padding: 5px 10px;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    animation: clignote 1s infinite;
">⚠️ OBJET</a>

<style>
@keyframes clignote {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
}
</style>
HTML;
?>
