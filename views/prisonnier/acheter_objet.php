<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/check_role.php';

// Vérifie que seul le rôle 'prisonnier' peut accéder
checkRole('prisonnier');


$user_id = $_SESSION['user']['id'];
$pageTitle = "Boutique d'objets interdits";

$stmt = $pdo->prepare("
    SELECT p.id AS prisonnier_id, u.argent 
    FROM prisonnier p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE p.utilisateur_id = ?
");
$stmt->execute([$user_id]);
$prisonnierData = $stmt->fetch();

if (!$prisonnierData) {
    echo "⚠️ Profil prisonnier introuvable.";
    exit;
}

$prisonnier_id = $prisonnierData['prisonnier_id'];
$solde = $prisonnierData['argent'];


// 📦 Objets disponibles
$objetsDispo = $pdo->query("
    SELECT id, nom, description, prix, interdit
    FROM objets_disponibles
    ORDER BY interdit DESC, prix ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 💾 Traitement d’achat
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['objet_id'])) {
    $objet_id = intval($_POST['objet_id']);

    $stmt = $pdo->prepare("SELECT * FROM objets_disponibles WHERE id = ?");
    $stmt->execute([$objet_id]);
    $objet = $stmt->fetch();

    if (!$objet) {
        $message = "❌ Objet introuvable.";
    } elseif ($solde < $objet['prix']) {
        $message = "❌ Solde insuffisant.";
    } else {
        // 🏦 Déduire l'argent du prisonnier
        // 🏦 Déduire l'argent du prisonnier (mise à jour de la table users)
        $stmt = $pdo->prepare("UPDATE users SET argent = argent - ? WHERE id = ?");
        $stmt->execute([$objet['prix'], $user_id]);


// 📥 Ajouter l'objet à l’inventaire du prisonnier
$stmt1 = $pdo->prepare("
    INSERT INTO objets_prisonniers (prisonnier_id, objet_id, nom_objet, description, interdit)
    VALUES (?, ?, ?, ?, ?)
");
$stmt1->execute([
    $prisonnier_id,
    $objet['id'],
    $objet['nom'],
    $objet['description'],
    $objet['interdit']
]);

// 🔔 Création d'une notification d'achat
$stmt2 = $pdo->prepare("
    INSERT INTO notifications (message, recipient_id, sender_id, type, is_read, created_at)
    VALUES (?, ?, ?, ?, 0, NOW())
");
$notifMessage = "Vous avez acheté « {$objet['nom']} » pour " . number_format($objet['prix'], 2) . " €.";
$stmt2->execute([$notifMessage, $user_id, $user_id, 'achat']);


        $message = "✅ Achat effectué avec succès.";
        $solde -= $objet['prix']; // MAJ affichage local
    }
}

$customHeadStyle = <<<CSS

        .dashboard-container { padding: 20px; max-width: 1000px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
        th { background-color: #2c2c2c; color: white; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        button { padding: 6px 12px; cursor: pointer; }


CSS;
    
?>

<!DOCTYPE html>
<html lang="fr">
    <?php include '../../includes/head.php'; ?>

<body>

<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>🛒 Boutique clandestine</h2>

    <p>💰 Solde actuel : <strong><?= number_format($solde, 2, ',', ' ') ?> €</strong></p>

    <?php if (!empty($message)): ?>
        <p class="<?= str_starts_with($message, '✅') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="post" action="">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($objetsDispo as $objet): ?>
                    <tr>
                        <td><?= htmlspecialchars($objet['nom']) ?></td>
                        <td><?= htmlspecialchars($objet['description']) ?></td>
                        <td><?= number_format($objet['prix'], 2, ',', ' ') ?> €</td>
                        <td style="color:<?= $objet['interdit'] ? 'red' : 'green' ?>;">
                            <?= $objet['interdit'] ? '❌ Interdit' : '✔️ Autorisé' ?>
                        </td>
                        <td>
                            <button type="submit" name="objet_id" value="<?= intval($objet['id']) ?>">Acheter</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

</body>
</html>
