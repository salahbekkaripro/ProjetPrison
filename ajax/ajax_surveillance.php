<?php
require_once '../includes/db.php';

if (isset($_GET['prisonnier_id'])) {
    $stmt = $pdo->prepare("SELECT cellule_id FROM prisonnier WHERE id = ?");
    $stmt->execute([$_GET['prisonnier_id']]);
    $celluleId = $stmt->fetchColumn();
    if ($celluleId) {
        afficherCellule($pdo, $celluleId);
    } else {
        echo "<p style='color:red;'>Ce prisonnier n'a pas de cellule assignée.</p>";
    }
} elseif (isset($_GET['cellule_id'])) {
    afficherCellule($pdo, $_GET['cellule_id']);
} else {
    echo "<p style='color:red;'>Paramètre invalide.</p>";
    exit;
}

function afficherCellule($pdo, $celluleId) {
    $stmt = $pdo->prepare("
        SELECT u.nom, u.prenom, p.etat, p.id
        FROM prisonnier p
        JOIN users u ON u.id = p.utilisateur_id
        WHERE p.cellule_id = ?
    ");
    $stmt->execute([$celluleId]);
    $prisonniers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($prisonniers)) {
        echo "<p>Aucun prisonnier dans cette cellule.</p>";
        return;
    }

    echo "<h4>👮 Prisonniers dans la cellule</h4>";
    echo "<table><thead><tr><th>Nom</th><th>Prénom</th><th>État</th><th>Action</th></tr></thead><tbody>";
    foreach ($prisonniers as $p) {
        echo "<tr>
                <td>" . htmlspecialchars($p['nom']) . "</td>
                <td>" . htmlspecialchars($p['prenom']) . "</td>
                <td>" . htmlspecialchars($p['etat']) . "</td>
                <td>
                    <form method='post' action='../admin/mettre_surveillance.php' style='margin:0;'>
                        <input type='hidden' name='prisonnier_id' value='" . $p['id'] . "'>
                        <button type='submit'>👁️ Surveiller 2h</button>
                    </form>
                </td>
            </tr>";
    }
    echo "</tbody></table>";
}