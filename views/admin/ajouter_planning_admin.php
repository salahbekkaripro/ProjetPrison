<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';
require_once '../../includes/check_role.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

checkRole('admin');

$pageTitle = "Créer un planning - Admin";
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilisateur_id = intval($_POST['utilisateur_id'] ?? 0);
    $jour = $_POST['jour'] ?? '';
    $heure_debut = $_POST['heure_debut'] ?? '';
    $heure_fin = $_POST['heure_fin'] ?? '';
    $activite = $_POST['activite'] ?? '';

    if ($utilisateur_id && $jour && $heure_debut && $heure_fin && $activite) {
        if ($heure_debut >= $heure_fin) {
            $message = "❌ L'heure de début doit être inférieure à l'heure de fin.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO planning (utilisateur_id, jour, heure_debut, heure_fin, activite) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$utilisateur_id, $jour, $heure_debut, $heure_fin, $activite]);
            $message = "✅ Planning ajouté avec succès.";
        }
    } else {
        $message = "❌ Tous les champs sont requis.";
    }
}

$users = $pdo->query("SELECT id, nom, prenom, role FROM users ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);

function generateTimeOptions() {
    $options = '';
    for ($h = 8; $h <= 20; $h++) {
        foreach (['00', '30'] as $m) {
            $time = sprintf('%02d:%s', $h, $m);
            $options .= "<option value=\"$time\">$time</option>";
        }
    }
    return $options;
}

$customHeadStyle = <<<CSS
        body {
            background-color: #121212;
            font-family: 'Segoe UI', sans-serif;
            color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 40px auto;
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(255, 215, 0, 0.2);
        }

        h2 {
            text-align: center;
            color: #ffd700;
            margin-bottom: 20px;
        }

        form {
            background-color: #2a2a2a;
            padding: 25px;
            border-radius: 10px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            color: #ccc;
        }

        select, input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 6px;
            border: none;
            background-color: #3a3a3a;
            color: white;
            font-size: 1rem;
        }

        select:focus, input[type="submit"]:hover {
            outline: none;
            background-color: #505050;
        }

        input[type="submit"] {
            margin-top: 25px;
            background-color: #28a745;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #218838;
        }

        .message {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }

        .sort-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #dc3545;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
        }

        .sort-btn:hover {
            background-color: #bd2130;
        }


CSS;
    
    
    
?>
<!DOCTYPE html>
<html lang="fr">
    <?php include '../../includes/header.php'; ?>

<body>
<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>📅 Créer un planning</h2>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="utilisateur_id">Utilisateur</label>
        <select name="utilisateur_id" required>
            <option value="">-- Choisir un utilisateur --</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>">
                    <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom'] . ' (' . $user['role'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="jour">Jour</label>
        <select name="jour" required>
            <?php foreach (["Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi","Dimanche"] as $jour): ?>
                <option value="<?= $jour ?>"><?= $jour ?></option>
            <?php endforeach; ?>
        </select>

        <label for="heure_debut">Heure de début</label>
        <select name="heure_debut" required>
            <?= generateTimeOptions() ?>
        </select>

        <label for="heure_fin">Heure de fin</label>
        <select name="heure_fin" required>
            <?= generateTimeOptions() ?>
        </select>

        <label for="activite">Activité</label>
        <select name="activite" required>
            <option value="">-- Choisir une activité --</option>
            <option value="Cellule">Cellule</option>
            <option value="Douche">Douche</option>
            <option value="Cantine">Cantine</option>
            <option value="Promenade">Promenade</option>
        </select>

        <input type="submit" value="Créer le planning">
    </form>

    <div style="text-align:center;">
        <a href="../gestionnaire/gestion_planning.php" class="sort-btn">⬅ Retour à la gestion</a>
    </div>
</div>
</body>
</html>