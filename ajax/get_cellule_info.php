<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

$stmt = $pdo->prepare("SELECT numero_cellule, capacite, surveillance FROM cellule WHERE id = ?");
$stmt->execute([$id]);
$cellule = $stmt->fetch();

if ($cellule) {
    echo json_encode([
        'success' => true,
        'numero' => $cellule['numero_cellule'],
        'capacite' => $cellule['capacite'],
        'surveillance' => $cellule['surveillance']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Cellule introuvable']);
}
