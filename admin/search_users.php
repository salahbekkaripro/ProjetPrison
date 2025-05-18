<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_admin_login();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE username LIKE :search ORDER BY username ASC LIMIT 10");
$stmt->execute([':search' => "%$query%"]);
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($users);
