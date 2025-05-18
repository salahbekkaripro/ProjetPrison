<?php
require_once '../includes/db.php';

$etat_order = ['sain' => 'malade', 'malade' => 'blessé', 'blessé' => 'décédé'];

// Prisonniers à vérifier (1 semaine ou plus sans maj)
$stmt = $pdo->query("
    SELECT id, etat, derniere_maj_etat
    FROM prisonnier
    WHERE etat != 'décédé' AND DATEDIFF(CURDATE(), derniere_maj_etat) >= 7
");

$prisonniers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($prisonniers as $p) {
    $etat_actuel = $p['etat'];
    if (isset($etat_order[$etat_actuel])) {
        $nouvel_etat = $etat_order[$etat_actuel];

        $update = $pdo->prepare("UPDATE prisonnier SET etat = ?, derniere_maj_etat = CURDATE() WHERE id = ?");
        $update->execute([$nouvel_etat, $p['id']]);

        echo "👎 Prisonnier #{$p['id']} : $etat_actuel → $nouvel_etat\n";
    }
}
?>
