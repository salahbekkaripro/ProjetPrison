<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

// Vérifie que seul le rôle 'gestionnaire' peut accéder
checkRole('gestionnaire');

if ($_SESSION['user']['role'] !== 'gestionnaire') {
    echo "⛔ Accès réservé aux gestionnaires.";
    exit;
}

$pageTitle = "📦 Gestion du stock de la prison";

// 🔄 Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    if ($delete_id > 0) {
        // Vérifie si l'objet à supprimer est "Clé artisanale" ou "Plan de la prison"
        $stmt = $pdo->prepare("SELECT nom FROM objets_disponibles WHERE id = ?");
        $stmt->execute([$delete_id]);
        $obj = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($obj) {
            // Si l'objet est "Clé artisanale" ou "Plan de la prison", on empêche la suppression
            if (in_array($obj['nom'], ['Clé artisanale', 'Plan de la prison'])) {
                $errorMessage = "❌ La suppression de cet objet est interdite.";
            } else {
                // Si l'objet n'est pas dans la liste des objets interdits, on le supprime
                $stmt = $pdo->prepare("DELETE FROM objets_disponibles WHERE id = ?");
                $stmt->execute([$delete_id]);
                $successMessage = "🗑️ Objet supprimé avec succès.";
            }
        } else {
            $errorMessage = "❌ Objet introuvable.";
        }
    } else {
        $errorMessage = "❌ ID invalide pour la suppression.";
    }
}


// ➕ Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_nom'])) {
    $nom = trim($_POST['add_nom']);
    $description = trim($_POST['add_description']);
    $prix = floatval($_POST['add_prix']);
    $interdit = isset($_POST['add_interdit']) ? 1 : 0;
    $type = $_POST['add_type'];  // Capture the selected type of the object

    if ($nom && $description && $prix >= 0) {
        $stmt = $pdo->prepare("INSERT INTO objets_disponibles (nom, description, prix, interdit, type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$nom, $description, $prix, $interdit, $type]);
        $successMessage = "✅ Objet ajouté avec succès.";
    } else {
        $errorMessage = "❌ Champs invalides.";
    }
}

$objets = $pdo->query("SELECT * FROM objets_disponibles ORDER BY interdit DESC, nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$customHeadStyle = <<<CSS

        .dashboard-container { padding: 20px; max-width: 1000px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: center; }
        th { background-color: rgb(151, 27, 27); color: white; }
        input, button, select { padding: 8px 12px; margin: 5px 0; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
            border: none;
            border-radius: 6px;
            background-color: #e74c3c;
            color: white;
            cursor: pointer;
        }
        .btn-neon {
            background-color: limegreen;
            color: black;
            font-weight: bold;
            border-radius: 8px;
            border: none;
            padding: 10px 20px;
            margin-top: 10px;
            cursor: pointer;
        }

        .message-box {
            padding: 10px;
            font-weight: bold;
            margin: 10px 0;
            border-radius: 6px;
        }
        .message-success { color: lime; }
        .message-error { color: red; }

CSS;
    

?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>

<body>
<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>📦 Gestion du stock de la prison</h2>

    <?php if (isset($successMessage)): ?>
        <div class="message-box message-success"><?= $successMessage ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="message-box message-error"><?= $errorMessage ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Prix</th>
                <th>Interdit ?</th>
                <th>Type</th> <!-- Added the Type column -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($objets as $obj): ?>
                <tr>
                    <td><?= htmlspecialchars($obj['nom']) ?></td>
                    <td><?= htmlspecialchars($obj['description']) ?></td>
                    <td><?= number_format($obj['prix'], 2) ?> €</td>
                    <td><?= $obj['interdit'] ? '❌ Oui' : '✅ Non' ?></td>
                    <td><?= htmlspecialchars($obj['type']) ?></td> <!-- Display object type -->
                    <td>
                        <form method="post" onsubmit="return confirm('Supprimer cet objet ?');" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= $obj['id'] ?>">
                            <button type="submit" class="btn-sm">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin-top: 40px;">➕ Ajouter un nouvel objet</h3>
    <form method="post">
        <input type="text" name="add_nom" placeholder="Nom de l'objet" required>
        <input type="text" name="add_description" placeholder="Description" required>
        <input type="number" name="add_prix" placeholder="Prix (€)" min="0" step="0.01" required>
        
        <!-- Type of the object dropdown -->
        <select name="add_type" required>
            <option value="alimentation">Alimentation</option>
            <option value="outil">Outil</option>
            <option value="divertissement">Divertissement</option>
            <option value="autre">Autre</option>
            <option value="arme">Arme</option>
            <option value="chimique">Chimique</option>
        </select>

        <label><input type="checkbox" name="add_interdit"> Objet interdit ?</label>
        <br>
        <button type="submit" class="btn-neon">💾 Ajouter</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
