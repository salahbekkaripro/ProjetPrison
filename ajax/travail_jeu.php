<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    echo json_encode(['success' => false, 'error' => '⛔ Accès refusé']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$choix = intval($data['nombre'] ?? 0);
$gagnant = random_int(1, 5); // ✅ plus sécurisé
$gain = 50;
$user_id = $_SESSION['user']['id'];

if ($choix < 1 || $choix > 5) {
    echo json_encode(['success' => false, 'message' => '❌ Choix invalide.']);
    exit;
}

if ($choix === $gagnant) {
    $stmt = $pdo->prepare("UPDATE users SET argent = argent + ? WHERE id = ?");
    $stmt->execute([$gain, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => "🎉 Bravo ! Vous avez deviné le bon numéro ($gagnant) et gagné $gain € !"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "❌ Mauvais numéro. Le bon était $gagnant."
    ]);
}
