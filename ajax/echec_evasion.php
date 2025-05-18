<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

$current_user_id = $_SESSION['user']['id'];

// 📌 Récupérer prisonnier_id
$stmt = $pdo->prepare("
    SELECT p.id AS prisonnier_id, u.nom, u.prenom, u.argent
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$current_user_id]);
$prisonnier = $stmt->fetch();

if (!$prisonnier) {
    echo json_encode(['error' => 'Prisonnier introuvable.']);
    exit;
}

$prisonnier_id = $prisonnier['prisonnier_id'];
$nomComplet = $prisonnier['prenom'] . ' ' . $prisonnier['nom'];
$argent = floatval($prisonnier['argent']);
$duree = 10;
$montant = 1000;

$date_now = (new DateTime())->format('Y-m-d H:i:s');
$fin_sanction = (new DateTime())->modify("+$duree days")->format('Y-m-d H:i:s');

// ✅ 1. Créer une infraction "tentative évasion"
$stmt = $pdo->prepare("
    INSERT INTO infraction (prisonnier_id, type_infraction, date_infraction)
    VALUES (?, 'tentative évasion', ?)
");
$stmt->execute([$prisonnier_id, $date_now]);

$infraction_id = $pdo->lastInsertId();

// ✅ 2. Appliquer une sanction liée
$pdo->prepare("
    INSERT INTO sanction (
        infraction_id, prisonnier_id, type_sanction, gravite, 
        duree_jours, commentaire, date_sanction, fin_sanction
    ) VALUES (
        ?, ?, 'mise_au_trou', 'grave', ?, 'Échec de tentative d’évasion', ?, ?
    )
")->execute([$infraction_id, $prisonnier_id, $duree, $date_now, $fin_sanction]);

// 💸 3. Retirer 1000 €
$pdo->prepare("UPDATE users SET argent = GREATEST(argent - ?, 0) WHERE id = ?")
    ->execute([$montant, $current_user_id]);

// 🔔 4. Notification au prisonnier
$pdo->prepare("
    INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
    VALUES (?, ?, 'sanction_appliquee', ?, 0, NOW())
")->execute([
    $current_user_id,
    $current_user_id,
    "🚨 Tentative d’évasion échouée : mise au trou ($duree jours) + amende 1000€."
]);
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

$msg = "🔐 Le prisonnier <strong>$nomComplet</strong> a tenté de s’évader mais a été intercepté.";

echo json_encode(['success' => true]);
