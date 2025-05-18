<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$role = $_GET['role'] ?? '';

if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE role = 'admin' ORDER BY username ASC");
} elseif ($role === 'user') {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE role = 'user' OR role IS NULL OR role = '' ORDER BY username ASC");
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute();
$usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($usernames);
?>
