<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    echo json_encode(['success' => false, 'error' => 'Accès interdit']);
    exit;
}

$admin_id = $_SESSION['user']['id'];
$pot_id = intval($_POST['pot_id'] ?? 0);
var_dump($_SESSION['user']);

if (!$pot_id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

// 🔍 Récupération du pot-de-vin
$stmt = $pdo->prepare("
    SELECT pd.*, p.id AS prisonnier_id, u.id AS utilisateur_id, u.nom, u.prenom
    FROM pots_de_vin pd
    JOIN prisonnier p ON pd.prisonnier_id = p.id
    JOIN users u ON p.utilisateur_id = u.id
    WHERE pd.id = ?
");
$stmt->execute([$pot_id]);
$pot = $stmt->fetch();

if (!$pot || $pot['admin_id'] != $admin_id) {
    echo json_encode(['success' => false, 'error' => 'Pot-de-vin non autorisé ou introuvable']);
    exit;
}

$prisonnier_id = $pot['prisonnier_id'];
$prisonnier_user_id = $pot['utilisateur_id'];
$nom_prisonnier = $pot['prenom'] . ' ' . $pot['nom'];

// ⛔ Marquer comme refusé
$stmt = $pdo->prepare("UPDATE pots_de_vin SET statut = 'refusé' WHERE id = ?");
$stmt->execute([$pot_id]);

// ❌ Supprimer objets interdits
$stmt = $pdo->prepare("DELETE FROM objets_prisonniers WHERE prisonnier_id = ? AND interdit = 1");
$stmt->execute([$prisonnier_id]);

// 🚨 Créer une infraction
$stmt = $pdo->prepare("
    INSERT INTO infraction (prisonnier_id, type_infraction, sanction, date_infraction, pot_id)
    VALUES (?, 'possession objet interdit', 'Objet confisqué après refus du pot-de-vin', NOW(), ?)
");
$stmt->execute([$prisonnier_id, $pot_id]);

// 🔔 Notification au prisonnier
$message = "❌ Le admin a refusé le pot-de-vin. Une infraction a été enregistrée et vos objets interdits ont été confisqués.";

$stmt = $pdo->prepare("
    INSERT INTO notifications (recipient_id, sender_id, type, message, pot_id, is_read, created_at)
    VALUES (?, ?, 'reponse_pot_de_vin', ?, ?, 0, NOW())
");
$stmt->execute([$prisonnier_user_id, $admin_id, $message, $pot_id]);

echo json_encode(['success' => true, 'message' => 'Pot refusé, infraction créée.']);
