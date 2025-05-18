<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

$attaquant_id = $_SESSION['user']['prisonnier_id'] ?? null;
$cible_id = $_SESSION['attaque']['cible_id'] ?? null;

if (!$attaquant_id || !$cible_id) {
    echo "❌ Données manquantes."; exit;
}

$attaque_reussie = isset($_POST['win']); // Joueur a cliqué dans le temps

// Récupération de la cible
$stmt = $pdo->prepare("SELECT p.etat, p.utilisateur_id, u.nom, u.prenom FROM prisonnier p JOIN users u ON p.utilisateur_id = u.id WHERE p.id = ?");
$stmt->execute([$cible_id]);
$cible = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cible || $cible['etat'] === 'décédé') {
    echo "❌ Cible invalide ou déjà décédée."; exit;
}

$etat_courant = $cible['etat'];
$newEtat = match ($etat_courant) {
    'sain' => 'malade',
    'malade' => 'blessé',
    'blessé' => 'décédé',
    default => null
};

$difficulte = $_SESSION['attaque']['difficulte'] ?? 'moyen';
$etat_resultat = "❌ Attaque échouée !";

if ($attaque_reussie && $newEtat) {
    $pdo->prepare("UPDATE prisonnier SET etat = ?, derniere_maj_etat = NOW() WHERE id = ?")
        ->execute([$newEtat, $cible_id]);

    // Notif
    $pdo->prepare("INSERT INTO notifications (recipient_id, message, type, sender_id, created_at)
                   VALUES (?, ?, 'sante_degradee', ?, NOW())")
        ->execute([$cible['utilisateur_id'], "Vous avez été attaqué ! État : <strong>$newEtat</strong>.", $user_id]);

    $etat_resultat = "✅ Attaque réussie ! <strong>{$cible['prenom']} {$cible['nom']}</strong> est maintenant <strong>$newEtat</strong>.";
}

unset($_SESSION['attaque']);

$customHeadStyle = <<<CSS

 .box-result {
            background: #111;
            color: white;
            padding: 30px;
            border: 2px solid #333;
            border-radius: 10px;
            max-width: 700px;
            margin: 80px auto;
            text-align: center;
        }
        .btn-retour {
            margin-top: 30px;
            background: #1e90ff;
            padding: 10px 20px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

CSS;
    
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>
<body>
    <div class="box-result">
        <h2><?= $attaque_reussie ? "🎯 Attaque réussie !" : "💥 Échec de l’attaque" ?></h2>
        <p>Difficulté : <strong><?= ucfirst($difficulte) ?></strong></p>
        <p><?= $etat_resultat ?></p>
        <a href="objet.php" class="btn-retour">⬅️ Retour aux objets</a>
    </div>
</body>
</html>
