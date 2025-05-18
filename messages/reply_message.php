<?php
session_start();
require_once '../includes/functions.php';
require_user_login();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = (int) $_POST['message_id'];
    $content = trim($_POST['content']);
    $sender_id = $_SESSION['user']['id'];

    if ($content !== '') {
        $stmt = $pdo->prepare("INSERT INTO private_message_replies (message_id, sender_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$message_id, $sender_id, $content]);
    }

    echo json_encode([
        'status' => 'success',
        'content' => htmlspecialchars($content),
        'created_at' => date('d/m/Y H:i'),
        'sender' => $_SESSION['user']['username']
    ]);
}
