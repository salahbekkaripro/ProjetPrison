<?php
require_once '../includes/db.php';

$now = date('Y-m-d H:i:s');

// Refuser tous les pots-de-vin expirés (en attente + délai dépassé)
$stmt = $pdo->prepare("
    UPDATE pots_de_vin
    SET statut = 'refuse'
    WHERE statut = 'en_attente' AND expire_at <= ?
");
$stmt->execute([$now]);

echo json_encode([
    'success' => true,
    'updated' => $stmt->rowCount()
]);
