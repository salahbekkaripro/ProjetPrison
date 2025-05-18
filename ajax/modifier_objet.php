<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'gestionnaire') {
    echo json_encode(['success' => false, 'error' => '⛔ Accès interdit.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$nom = trim($_POST['nom'] ?? '');
$description = trim($_POST['description'] ?? '');
$prix = floatval($_POST['prix'] ?? 0);
$interdit = isset($_POST['interdit']) ? 1 : 0;

if ($id <= 0 || !$nom || !$description || $prix < 0) {
    echo json_encode(['success' => false, 'error' => 'Champs invalides.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE objets_disponibles SET nom = ?, description = ?, prix = ?, interdit = ? WHERE id = ?");
$stmt->execute([$nom, $description, $prix, $interdit, $id]);

echo json_encode(['success' => true]);
