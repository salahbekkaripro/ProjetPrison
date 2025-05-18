<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

$user_id = $_SESSION['user']['id'];

// Supprimer le prisonnier (user + profil)
$pdo->prepare("DELETE FROM prisonnier WHERE utilisateur_id = ?")->execute([$user_id]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
// 🔍 Récupérer nom et prénom du prisonnier
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch();
$nomComplet = $user ? $user['prenom'] . ' ' . $user['nom'] : 'Un prisonnier inconnu';
$msg = "🚨 Le prisonnier <strong>$nomComplet</strong> s’est évadé avec succès ! Les admins sont en alerte.";

session_destroy();

echo json_encode(['success' => true]);
