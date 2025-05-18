<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

require_user_login();




$pageTitle = "Traitement de l'infraction";
require_once '../../includes/head.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prisonnier_id = intval($_POST['prisonnier_id'] ?? 0);
    $type = trim($_POST['type_infraction'] ?? '');
    $sanction = trim($_POST['sanction'] ?? '');
    $pot_id = intval($_POST['pot_id'] ?? 0);

    if ($prisonnier_id <= 0 || empty($type) || empty($sanction)) {
        echo "<div class='container' style='color:red; text-align:center; margin-top:50px; font-weight:bold;'>❌ Tous les champs sont requis.</div>";
        exit;
    }

    // ❌ Empêcher la duplication si pot_id existe déjà
    if ($pot_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM infraction WHERE pot_id = ?");
        $stmt->execute([$pot_id]);
        if ($stmt->fetch()) {
            ?>
            <audio id="alertSound" src="../assets/sounds/error.mp3" preload="auto"></audio>
            <div id="universal-overlay" style="
                position: fixed;
                top: 0; left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                color: red;
                font-size: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                font-weight: bold;
                text-align: center;
                padding: 30px;
                display: none;"></div>
            <div id="flash-overlay" style="
                position: fixed;
                top: 0; left: 0;
                width: 100%;
                height: 100%;
                background: white;
                opacity: 0;
                z-index: 9998;
                transition: opacity 0.5s;"></div>
            <script>
                function playOverlayAnimation({ message, soundId, redirectTo }) {
                    const overlay = document.getElementById('universal-overlay');
                    const flash = document.getElementById('flash-overlay');
                    const audio = document.getElementById(soundId);
                    let i = 0;

                    if (!overlay || !flash || !audio) return;

                    overlay.textContent = '';
                    overlay.style.display = 'flex';
                    audio.play();

                    const typewriter = setInterval(() => {
                        if (i < message.length) {
                            overlay.textContent += message[i];
                            i++;
                        } else {
                            clearInterval(typewriter);

                            setTimeout(() => {
                                overlay.remove();
                                flash.style.opacity = 1;
                                setTimeout(() => {
                                    window.location.href = redirectTo;
                                }, 700);
                            }, 1000);
                        }
                    }, 70);
                }

                playOverlayAnimation({
                    message: "⛔ Une infraction a déjà été créée pour ce pot-de-vin.",
                    soundId: "alertSound",
                    redirectTo: "../views/admin_dashboard.php"
                });
            </script>
            <?php
            exit;
        }
    }

    // ✅ Insertion
    $stmt = $pdo->prepare("
        INSERT INTO infraction (prisonnier_id, type_infraction, date_infraction, sanction, pot_id)
        VALUES (?, ?, NOW(), ?, ?)
    ");
    $stmt->execute([$prisonnier_id, $type, $sanction, $pot_id > 0 ? $pot_id : null]);

    // ✅ Affichage confirmation
    $stmt2 = $pdo->prepare("
        SELECT u.nom, u.prenom 
        FROM users u
        JOIN prisonnier p ON u.id = p.utilisateur_id
        WHERE p.id = ?
    ");
    $stmt2->execute([$prisonnier_id]);
    $prisonnier = $stmt2->fetch();
    $nom_complet = $prisonnier ? htmlspecialchars($prisonnier['prenom'] . ' ' . $prisonnier['nom']) : "le prisonnier";
    ?>
    <head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
    <div class="container" style="max-width: 800px; margin: auto; margin-top: 60px;">
        <div class="glass-box" style="padding: 30px; border-radius: 12px;">
            <h2 style="color: #28a745; font-size: 1.6rem; text-align: center;">✅ Infraction enregistrée avec succès !</h2>
            <p style="color:white; text-align: center; margin-top: 10px;">
                Une infraction a été ajoutée pour <strong style="color:orange;"><?= $nom_complet ?></strong>.
            </p>
        </div>
    </div>
    <?php
    exit;
}

// Accès direct
echo "<div class='container' style='text-align:center; margin-top:60px; color:white;'>⛔ Accès direct interdit.</div>";
exit;
?>