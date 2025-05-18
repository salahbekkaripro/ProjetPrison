<?php
session_start();
require_once '../includes/functions.php';
require_user_login();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM private_messages WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$_POST['id'], $_SESSION['user']['id']]);
}
