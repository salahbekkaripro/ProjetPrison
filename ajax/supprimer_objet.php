<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// 🔒 Vérifie si l'utilisateur est un gestionnaire
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'gestionnaire') {
    echo json_encode(['success' => false, 'error' => '⛔ Accès interdit.']);
    exit;
}

// 📥 Récupération de l’ID de l’objet
$objet_id = intval($_POST['id'] ?? 0);

if ($objet_id <= 0) {
    echo json_encode(['success' => false, 'error' => '❌ ID d\'objet invalide.']);
    exit;
}

// 🗑️ Suppression de l’objet
$stmt = $pdo->prepare("DELETE FROM objets_disponibles WHERE id = ?");
$success = $stmt->execute([$objet_id]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression.']);
}
