<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';


// Rôle prisonnier uniquement
checkRole('prisonnier');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Accès direct interdit.");
}

$cible_id = $_POST['cible_id'] ?? null;
$resultat = $_POST['resultat'] ?? null;
$objet_id = $_POST['objet_id'] ?? null;

if (!$cible_id || !$objet_id || !in_array($resultat, ['success', 'fail'])) {
    exit("Données invalides.");
}

// Récupérer les infos du prisonnier ciblé
$stmt = $pdo->prepare("SELECT id, etat, utilisateur_id FROM prisonnier WHERE id = ?");
$stmt->execute([$cible_id]);
$prisonnier = $stmt->fetch();

if (!$prisonnier) {
    exit("Prisonnier cible introuvable.");
}

$nouvel_etat = $prisonnier['etat'];
$etatMap = [
    'sain' => 100,
    'malade' => 70,
    'blessé' => 30,
    'décédé' => 0
];

$inverseMap = [
    100 => 'sain',
    70 => 'malade',
    30 => 'blessé',
    0 => 'décédé'
];

$etat_actuel = $prisonnier['etat'];
$niveau_actuel = $etatMap[$etat_actuel] ?? 100;

if ($resultat === 'success') {
    $niveau_nouveau = max(0, $niveau_actuel - 30); // ou 10 si tu veux
    // déduire le nouvel état
    if ($niveau_nouveau >= 100) $nouvel_etat = 'sain';
    elseif ($niveau_nouveau >= 70) $nouvel_etat = 'malade';
    elseif ($niveau_nouveau >= 30) $nouvel_etat = 'blessé';
    else $nouvel_etat = 'décédé';

    // mise à jour
    $stmt = $pdo->prepare("UPDATE prisonnier SET etat = ?, derniere_maj_etat = NOW() WHERE id = ?");
    $stmt->execute([$nouvel_etat, $cible_id]);

    // supprimer l'objet
    $deleteObj = $pdo->prepare("DELETE FROM objets_prisonniers WHERE prisonnier_id = ? AND objet_id = ?");
    $deleteObj->execute([$cible_id, $objet_id]);

    // notification
    $notifMessage = "Votre état est maintenant \"$nouvel_etat\" après une attaque.";
    $sender_id = $_SESSION['user']['id']; // utilisateur connecté
$stmt = $pdo->prepare("INSERT INTO notifications (recipient_id, sender_id, type, message, created_at, is_read) VALUES (?, ?, 'sante_degradee', ?, NOW(), 0)");
$stmt->execute([$prisonnier['utilisateur_id'], $sender_id, $notifMessage]);

} else {
    $nouvel_etat = $etat_actuel; // inchangé
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Résultat de l'attaque</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto:wght@400;700&display=swap');
        body {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: #f0f0f0;
            font-family: 'Roboto', sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        .result-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            box-shadow: 0 0 10px #00ff9f, 0 0 20px #00ff9f, 0 0 30px #00ff9f, 0 0 40px #00ff9f inset;
            max-width: 480px;
            padding: 40px 30px;
            animation: pulseGlow 2s infinite alternate;
            position: relative;
        }
        .success { border: 3px solid #00ff9f; color: #00ff9f; }
        .fail {
            border: 3px solid #ff4f4f;
            color: #ff4f4f;
            box-shadow: 0 0 10px #ff4f4f, 0 0 20px #ff4f4f, 0 0 30px #ff4f4f inset;
            animation: pulseGlowFail 2s infinite alternate;
        }
        h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            margin-bottom: 0.5em;
            text-shadow: 0 0 8px currentColor;
        }
        p {
            font-size: 1.3rem;
            margin: 15px 0;
            line-height: 1.4;
        }
        .etat {
            font-weight: 700;
            font-size: 1.6rem;
            margin-top: 25px;
            text-shadow: 0 0 10px currentColor;
        }
        .btn-return {
            margin-top: 35px;
            padding: 14px 30px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f2027;
            background: linear-gradient(45deg, #00ff9f, #00cc7a);
            border: none;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 0 10px #00ff9f, 0 0 20px #00ff9f, 0 0 30px #00ff9f;
            transition: background 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .btn-return:hover { background: linear-gradient(45deg, #00cc7a, #00ff9f); }
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 10px #00ff9f, 0 0 20px #00ff9f, 0 0 30px #00ff9f, 0 0 40px #00ff9f inset; }
            100% { box-shadow: 0 0 20px #00ff9f, 0 0 30px #00ff9f, 0 0 40px #00ff9f, 0 0 50px #00ff9f inset; }
        }
        @keyframes pulseGlowFail {
            0% { box-shadow: 0 0 10px #ff4f4f, 0 0 20px #ff4f4f, 0 0 30px #ff4f4f inset; }
            100% { box-shadow: 0 0 20px #ff4f4f, 0 0 30px #ff4f4f, 0 0 40px #ff4f4f inset; }
        }
    </style>
</head>
<body>
    <div class="result-card <?= $resultat === 'success' ? 'success' : 'fail' ?>">
        <?php if ($resultat === 'success'): ?>
            <h1>🎉 Succès !</h1>
            <p>Vous avez touché la cible.</p>
            <p class="etat">Santé du prisonnier a baissée à <strong><?= htmlspecialchars($nouvel_etat) ?></strong>.</p>
        <?php else: ?>
            <h1>❌ Échec !</h1>
            <p>Vous avez raté la cible.</p>
            <p class="etat">Santé du prisonnier reste à <strong><?= htmlspecialchars($nouvel_etat) ?></strong>.</p>
        <?php endif; ?>
        <button class="btn-return" onclick="window.location.href='prisonnier_dashboard.php'">↩ Revenir au tableau de bord</button>
    </div>
</body>
</html>