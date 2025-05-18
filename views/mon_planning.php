<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$user = $_SESSION['user'];
$user_id = $user['id'];
$pageTitle = "Mon planning validé";

$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
$horaires = [];
$start = strtotime('08:00:00');
$end = strtotime('20:00:00');
while ($start < $end) {
    $horaires[] = date('H:i:s', $start);
    $start += 1800;
}

$stmt = $pdo->prepare("SELECT * FROM planning WHERE utilisateur_id = ? AND validation = 'validé'");
$stmt->execute([$user_id]);
$planning = $stmt->fetchAll(PDO::FETCH_ASSOC);

$summary = [];
foreach ($planning as $event) {
    $act = $event['activite'];
    $debut = strtotime($event['heure_debut']);
    $fin = strtotime($event['heure_fin']);
    $duree = ($fin - $debut) / 3600;
    $summary[$act] = ($summary[$act] ?? 0) + $duree;
}

$cellsFusionnees = [];
function getActivityColor($activite) {
    return match ($activite) {
        'Cellule' => '#607d8b',
        'Douche' => '#9c27b0',
        'Cantine' => '#ff9800',
        'Promenade' => '#03a9f4',
        default => '#4caf50'
    };
}
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

            $color = getActivityColor($event['activite']);

            return "<td rowspan='$rowspan' style='background:$color; color:white; font-weight:bold; border-radius:6px;'>
                        {$event['activite']}<br><small>" . date('H:i', $debut) . " - " . date('H:i', $fin) . "</small>
                    </td>";
        }
    }
    return "<td></td>";
}
$customHeadStyle = <<<CSS
   body {
            background-color: #1a1a1a;
            font-family: 'Segoe UI', sans-serif;
            color: #f0f0f0;
        }
        .dashboard-container {
            padding: 30px;
            max-width: 1000px;
            margin: auto;
            background-color: #2b2b2b;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        }
        h2 {
            text-align: center;
            color: #ffd700;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #444;
            background-color: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #555;
            padding: 10px;
            text-align: center;
            font-size: 0.95rem;
        }
        th {
            background-color: #333;
            color: #f0f0f0;
        }
        td {
            background-color: #262626;
        }
        button {
            padding: 10px 20px;
            font-size: 1rem;
            margin: 5px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s ease-in-out;
        }
        button:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        .btn-print { background-color: #912; color: white; }
        .btn-pdf { background-color: #555; color: white; }
        .summary {
            margin-top: 25px;
            background: #1e1e1e;
            padding: 15px;
            border-radius: 8px;
        }
        .summary h3 {
            color: #ffd700;
        }
        .summary ul {
            list-style: none;
            padding-left: 0;
        }
        .summary li {
            margin: 5px 0;
        }
        @media print {
            body { background: white; color: black; }
            body * { visibility: hidden; }
            .dashboard-container, .dashboard-container * { visibility: visible; }
            .dashboard-container { position: relative; width: 100%; max-width: 1000px; margin: 0 auto; background: white; color: black; padding: 10px; }
            button, .selector, nav, header, footer { display: none !important; }
            table, tr, td, th { page-break-inside: avoid; break-inside: avoid; }
        }
CSS;
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.querySelector('.dashboard-container');
    const opt = {
        margin: [0.2, 0.2, 0.2, 0.2],
        filename: 'mon_planning.pdf',
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 1.2, scrollY: 0, useCORS: true },
        jsPDF: { unit: 'cm', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().from(element).set(opt).save();
}
</script>

<body>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>📅 Mon planning validé</h2>

    <?php if (empty($planning)): ?>
        <p style="text-align:center;">Aucun créneau validé n'est encore enregistré.</p>
    <?php else: ?>
        <div style="text-align:center; margin-bottom: 20px;">
            <button class="btn-print" onclick="window.print()">🖨️ Imprimer</button>
            <button class="btn-pdf" onclick="downloadPDF()">⬇️ Télécharger PDF</button>
        </div>

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

        <div class="summary">
            <h3>🧠 Résumé des heures par activité</h3>
            <ul>
                <?php foreach ($summary as $act => $hrs): ?>
                    <li><strong><?= htmlspecialchars($act) ?> :</strong> <?= number_format($hrs, 1) ?>h</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
