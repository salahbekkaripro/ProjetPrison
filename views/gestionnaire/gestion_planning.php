<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/db.php';

$pageTitle = "Validation des plannings en attente";
require_once '../../includes/check_role.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

// Vérifie que seul le rôle 'gestionnaire' peut accéder
checkRole('gestionnaire');

// Traitement des actions (valider/refuser)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['planning_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && in_array($action, ['valider', 'refuser'])) {
        $stmt = $pdo->prepare("UPDATE planning SET validation = ? WHERE id = ?");
        $stmt->execute([$action === 'valider' ? 'validé' : 'refusé', $id]);
    }
}

// Récupération des utilisateurs ayant des plannings "en attente"
$stmt = $pdo->query("
    SELECT u.id AS user_id, u.nom, u.prenom
    FROM users u
    JOIN planning p ON p.utilisateur_id = u.id
    WHERE p.validation = 'en attente'
    GROUP BY u.id, u.nom, u.prenom
    ORDER BY u.nom
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des plannings en attente, groupés par utilisateur
$planningsParUtilisateur = [];
$stmt = $pdo->query("
    SELECT p.*, u.id AS user_id
    FROM planning p
    JOIN users u ON p.utilisateur_id = u.id
    WHERE p.validation = 'en attente'
    ORDER BY p.jour, p.heure_debut
");
foreach ($stmt as $row) {
    $planningsParUtilisateur[$row['user_id']][] = $row;
}

$customHeadStyle = <<<CSS


    .user-block {
            margin: 10px auto;
            width: 90%;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color:rgb(97, 21, 21);
            cursor: pointer;
        }
        .user-header {
            padding: 12px 16px;
            font-weight: bold;
            background-color:rgb(151, 27, 27);
        }
        .planning-table {
            display: none;
            width: 100%;
            border-collapse: collapse;
        }
        .planning-table th, .planning-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        .planning-table th {
            background-color:rgb(180, 24, 24);
        }
        .btn-small {
            padding: 5px 10px;
            margin: 0 2px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-valider { background-color: #28a745; color: white; }
        .btn-refuser { background-color: #dc3545; color: white; }
    </style>
    <script>
        function togglePlanning(userId) {
            const table = document.getElementById('planning-' + userId);
            if (table.style.display === 'none' || table.style.display === '') {
                table.style.display = 'table';
            } else {
                table.style.display = 'none';
            }
        }
CSS;
    
?>



<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>

<body>
<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2 style="text-align:center; margin-top: 20px;">Plannings à valider</h2>

    <?php if (empty($users)): ?>
        <p style="text-align:center;">Aucun planning en attente de validation.</p>
    <?php endif; ?>

    <?php foreach ($users as $user): ?>
        <div class="user-block" onclick="togglePlanning(<?= $user['user_id'] ?>)">
            <div class="user-header">
                <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>
            </div>
            <table class="planning-table" id="planning-<?= $user['user_id'] ?>">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Heure Début</th>
                        <th>Heure Fin</th>
                        <th>Activité</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planningsParUtilisateur[$user['user_id']] ?? [] as $planning): ?>
                        <tr>
                            <td><?= $planning['jour'] ?></td>
                            <td><?= $planning['heure_debut'] ?></td>
                            <td><?= $planning['heure_fin'] ?></td>
                            <td><?= $planning['activite'] ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="planning_id" value="<?= $planning['id'] ?>">
                                    <button type="submit" name="action" value="valider" class="btn-small btn-valider">✔️</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="planning_id" value="<?= $planning['id'] ?>">
                                    <button type="submit" name="action" value="refuser" class="btn-small btn-refuser">❌</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div style="text-align:center; margin-top: 30px;">
        <a href="dashboard_admin.php" class="sort-btn">⬅ Retour au dashboard</a>
    </div>
</div>
</body>
</html>
