<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['gardien', 'chef'])) {
    echo json_encode(['success' => false, 'error' => 'Accès interdit']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$prisonnier_id = intval($data['prisonnier_id'] ?? 0);
$gardien_id = $_SESSION['user']['id'];

if (!$prisonnier_id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

// 🔍 Récupération infos
$stmt = $pdo->prepare("
    SELECT p.id, u.id AS utilisateur_id, u.nom, u.prenom 
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$prisonnier_id]);
$prisonnier = $stmt->fetch();

if (!$prisonnier) {
    echo json_encode(['success' => false, 'error' => 'Prisonnier introuvable']);
    exit;
}

$prisonnier_user_id = $prisonnier['utilisateur_id'];
$nom_prisonnier = $prisonnier['prenom'] . ' ' . $prisonnier['nom'];

// ❌ Supprimer objets interdits
$stmt = $pdo->prepare("DELETE FROM objets_prisonniers WHERE prisonnier_id = ? AND interdit = 1");
$stmt->execute([$prisonnier_id]);

// 🚨 Créer infraction
$stmt = $pdo->prepare("
    INSERT INTO infraction (prisonnier_id, type_infraction, sanction, date_infraction)
    VALUES (?, 'possession objet interdit', 'Objet interdit confisqué lors d\'une fouille', NOW())
");
$stmt->execute([$prisonnier_id]);

// 🔔 Notification au prisonnier
$stmt = $pdo->prepare("
    INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
    VALUES (?, ?, 'reponse_pot_de_vin', ?, 0, NOW())
");
$stmt->execute([
    $prisonnier_user_id,
    $gardien_id,
    "❌ Une infraction a été enregistrée pour possession d'objet interdit. Vos objets ont été confisqués."
]);

echo json_encode(['success' => true]);
