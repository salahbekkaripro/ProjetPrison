<?php
require_once '../includes/db.php';

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) exit;

// Jours & créneaux
$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
$horaires = [];
$h = strtotime('08:00:00');
$fin = strtotime('20:00:00');
while ($h < $fin) {
    $horaires[] = date('H:i:s', $h);
    $h += 1800;
}

// Chargement du planning validé
$stmt = $pdo->prepare("SELECT * FROM planning WHERE utilisateur_id = ? AND validation = 'validé'");
$stmt->execute([$user_id]);
$planning = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cellsFusionnees = [];

function afficherCellule($jour, $heure, $planning, &$fusionnees) {
    foreach ($planning as $event) {
        $debut = strtotime($event['heure_debut']);
        $fin = strtotime($event['heure_fin']);
        $current = strtotime($heure);

        if (isset($fusionnees[$jour][$heure])) return '';

        if ($event['jour'] === $jour && $debut === $current) {
            $rowspan = ($fin - $debut) / 1800;
            for ($i = $debut + 1800; $i < $fin; $i += 1800) {
                $fusionnees[$jour][date('H:i:s', $i)] = true;
            }
            return "<td rowspan='$rowspan' style='background:#d5f2c7; font-weight:bold;'>
                        {$event['activite']}<br><small>" . date('H:i', $debut) . " - " . date('H:i', $fin) . "</small>
                    </td>";
        }
    }
    return "<td></td>";
}

ob_start();
?>

<table>
    <thead>
        <tr>
            <th>Heure</th>
            <?php foreach ($jours as $jour): ?>
                <th><?= $jour ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($horaires as $heure): ?>
            <tr>
                <td><?= date('H:i', strtotime($heure)) ?></td>
                <?php foreach ($jours as $jour): ?>
                    <?= afficherCellule($jour, $heure, $planning, $cellsFusionnees); ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
echo ob_get_clean();
