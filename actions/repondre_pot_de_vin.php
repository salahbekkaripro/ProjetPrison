<?php
session_start();
require_once '../includes/db.php';

$user_id = $_SESSION['user']['id'] ?? null;
$pot_id = intval($_POST['pot_id'] ?? 0);
$action = $_POST['action'] ?? null;

if (!$user_id || !$pot_id || !in_array($action, ['payer', 'refuser'])) {
    die("⛔ Requête invalide.");
}

$accepte = $action === 'payer';

try {
    // 🔍 Récupérer le profil prisonnier
    $stmt = $pdo->prepare("
        SELECT p.id AS prisonnier_id, u.nom, u.prenom 
        FROM prisonnier p
        JOIN users u ON p.utilisateur_id = u.id
        WHERE p.utilisateur_id = ?
    ");
    $stmt->execute([$user_id]);
    $prisonnier = $stmt->fetch();

    if (!$prisonnier) {
        die("❌ Aucun profil prisonnier trouvé.");
    }

    $prisonnier_id = $prisonnier['prisonnier_id'];
    $nomComplet = $prisonnier['prenom'] . ' ' . $prisonnier['nom'];

    // 🔍 Vérifier que le pot-de-vin appartient à ce prisonnier
    $stmt = $pdo->prepare("SELECT * FROM pots_de_vin WHERE id = ? AND prisonnier_id = ?");
    $stmt->execute([$pot_id, $prisonnier_id]);
    $pot = $stmt->fetch();

    if (!$pot) {
        die("❌ Pot-de-vin non trouvé.");
    }

    $admin_id = $pot['admin_id'] ?? null;
    if (!$admin_id) {
        die("❌ admin non défini pour ce pot-de-vin.");
    }

    // 🛠 Mise à jour du statut
    $stmt = $pdo->prepare("UPDATE pots_de_vin SET statut = ? WHERE id = ?");
    $stmt->execute([$accepte ? 'accepté' : 'refusé', $pot_id]);

    // 🔔 Notification spéciale si refus
    if (!$accepte) {
        $messageRefus = "❌ Le prisonnier $nomComplet a refusé de payer le pot-de-vin. Cliquez ici pour créer une infraction.";

        $stmtNotif = $pdo->prepare("
            INSERT INTO notifications (recipient_id, sender_id, type, message, post_id, pot_id, is_read, created_at)
            VALUES (?, ?, 'infraction_suggeree', ?, NULL, ?, 0, NOW())
        ");
        $stmtNotif->execute([$admin_id, $user_id, $messageRefus, $pot_id]);
    }

    // 🔁 Notification de réponse générique
    $messageReponse = $accepte
        ? "✅ Le prisonnier $nomComplet a accepté et payé le pot-de-vin."
        : "❌ Le prisonnier $nomComplet a refusé de payer le pot-de-vin.";

    $stmtNotif2 = $pdo->prepare("
        INSERT INTO notifications (recipient_id, sender_id, type, message, post_id, pot_id, is_read, created_at)
        VALUES (?, ?, 'reponse_pot_de_vin', ?, NULL, ?, 0, NOW())
    ");
    $stmtNotif2->execute([$admin_id, $user_id, $messageReponse, $pot_id]);

    // ✅ Redirection finale
    header("Location: ../views/notifications.php");
    exit;

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
