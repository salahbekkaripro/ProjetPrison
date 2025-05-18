<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'chef'])) {
    echo json_encode(['success' => false, 'error' => 'Accès interdit']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$admin_id = $_SESSION['user']['id'] ?? null;
$prisonnier_id = intval($data['prisonnier_id'] ?? 0);
$montant = floatval($data['montant'] ?? 0);

if (!$admin_id || $prisonnier_id <= 0 || $montant <= 0) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

try {
    // 🔎 Récupère l'utilisateur lié au prisonnier
    $stmt = $pdo->prepare("
        SELECT u.nom, u.prenom, u.id AS utilisateur_id 
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

    $nomComplet = $prisonnier['nom'] . ' ' . $prisonnier['prenom'];
    $prisonnier_user_id = $prisonnier['utilisateur_id'];

    // ⏱️ Expiration dans 10 minutes
    $expireAt = (new DateTime())->modify('+10 minutes');
    $expireAtStr = $expireAt->format('Y-m-d H:i:s');
    $heureLimite = $expireAt->format('H:i');

    // 💾 Enregistrement dans pots_de_vin
    $stmt = $pdo->prepare("
        INSERT INTO pots_de_vin (admin_id, prisonnier_id, montant, expire_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$admin_id, $prisonnier_id, $montant, $expireAtStr]);
    $pot_id = $pdo->lastInsertId();

    // 🔔 Notification au prisonnier (⚠️ avec pot_id maintenant)
    $stmtNotif = $pdo->prepare("
        INSERT INTO notifications (recipient_id, sender_id, type, message, post_id, pot_id, is_read, created_at)
        VALUES (?, ?, 'pot_de_vin', ?, ?, ?, 0, NOW())
    ");
    $stmtNotif->execute([
        $prisonnier_user_id,
        $admin_id,
        "Le admin vous demande un pot-de-vin de {$montant}€ pour ne pas signaler un objet interdit. Répondez avant $heureLimite.",
        $pot_id, // post_id
        $pot_id  // pot_id
    ]);

    // 🔔 Notification au admin (facultative)
    $stmtNotifadmin = $pdo->prepare("
        INSERT INTO notifications (recipient_id, sender_id, type, message, post_id, pot_id, is_read, created_at)
        VALUES (?, ?, 'pot_de_vin_admin', ?, ?, ?, 0, NOW())
    ");
    $stmtNotifadmin->execute([
        $admin_id,
        $admin_id,
        "Vous avez demandé un pot-de-vin de {$montant}€ à $nomComplet.",
        $pot_id,
        $pot_id
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur SQL : ' . $e->getMessage()]);
}
