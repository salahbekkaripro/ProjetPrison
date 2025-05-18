<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';

checkRole('admin');
require_user_login();

$prisonnier_id = intval($_GET['prisonnier_id'] ?? 0);
$pot_id = intval($_GET['pot_id'] ?? 0);

// 🔍 Récupération du nom du prisonnier
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom 
    FROM users u
    INNER JOIN prisonnier p ON u.id = p.utilisateur_id
    WHERE p.id = ?
");
$stmt->execute([$prisonnier_id]);
$prisonnier = $stmt->fetch();

if (!$prisonnier) {
    echo "<p style='color:red; text-align:center;'>❌ Prisonnier introuvable.</p>";
    exit;
}

// 🔒 Vérifie si une infraction a déjà été créée pour ce pot-de-vin
if ($pot_id > 0) {
    $stmt = $pdo->prepare("SELECT id FROM infraction WHERE pot_id = ?");
    $stmt->execute([$pot_id]);
    $exist = $stmt->fetch();

    if ($exist) {
        ?>
        <style>
            #infraction-overlay {
                position: fixed;
                top: 0; left: 0;
                width: 100vw; height: 100vh;
                background: rgba(0, 0, 0, 0.92);
                color: #e74c3c;
                font-size: 2rem;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 40px;
                z-index: 9999;
                animation: fadeIn 0.4s ease-in-out;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: scale(0.95); }
                to   { opacity: 1; transform: scale(1); }
            }

            .typewriter span::after {
                content: '|';
                animation: blink 0.8s infinite;
            }

            @keyframes blink {
                0%, 100% { opacity: 1; }
                50% { opacity: 0; }
            }
        </style>

        <div id="infraction-overlay">
            <div class="typewriter"><span id="infraction-message"></span></div>
        </div>

        <script>
            const message = "⛔ Une infraction a déjà été créée pour ce pot-de-vin.";
            const textEl = document.getElementById('infraction-message');
            let i = 0;

            function type() {
                if (i < message.length) {
                    textEl.innerHTML += message.charAt(i);
                    i++;
                    setTimeout(type, 60);
                } else {
                    setTimeout(() => {
                        window.location.href = '../notifications.php';
                    }, 1500);
                }
            }

            type();
        </script>
        <?php
        exit;
    }
}

// 🔥 Suppression des objets interdits du prisonnier
$stmt = $pdo->prepare("DELETE FROM objets_prisonniers WHERE prisonnier_id = ? AND interdit = 1");
$stmt->execute([$prisonnier_id]);

$pageTitle = "Créer une infraction";
require_once '../../includes/head.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<style>
    #form-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.9);
        color: limegreen;
        font-size: 2rem;
        font-weight: bold;
        font-family: 'Courier New', Courier, monospace;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        text-align: center;
        padding: 40px;
        animation: fadeIn 0.4s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to   { opacity: 1; transform: scale(1); }
    }

    .typewriter span::after {
        content: '|';
        animation: blink 0.8s infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }

    #infraction-form-container {
        display: none;
    }
</style>

<div id="form-overlay">
    <div class="typewriter"><span id="overlay-message"></span></div>
</div>

<script>
    const msg = "✅ Vous pouvez enregistrer une infraction.";
    const span = document.getElementById("overlay-message");
    const container = document.getElementById("form-overlay");
    const formBox = document.getElementById("infraction-form-container");
    let i = 0;

    function typeOverlay() {
        if (i < msg.length) {
            span.innerHTML += msg[i];
            i++;
            setTimeout(typeOverlay, 50);
        } else {
            setTimeout(() => {
                container.remove();
                document.getElementById("infraction-form-container").style.display = "block";
            }, 1000);
        }
    }

    typeOverlay();
</script>

<!-- ✅ FORMULAIRE INFRACTION -->
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<div id="infraction-form-container" class="container" style="max-width: 800px; margin: auto; margin-top: 60px;">
    <h2 style="color:white; font-size: 1.8rem; margin-bottom: 25px;">
        🚨 Créer une infraction pour <span style="color: orange;">
        <?= htmlspecialchars($prisonnier['prenom'] . ' ' . $prisonnier['nom']) ?>
        </span>
    </h2>

    <form method="POST" action="../gestionnaire/traitement_nouvelle_infraction.php" class="glass-box" style="padding: 25px; border-radius: 12px;">
        <input type="hidden" name="prisonnier_id" value="<?= $prisonnier_id ?>">
        <input type="hidden" name="pot_id" value="<?= $pot_id ?>">

        <label for="type_infraction" style="color:white; font-weight:bold;">Type d'infraction :</label>
        <select name="type_infraction" required style="width:100%; padding: 10px; margin: 10px 0; border-radius: 6px;">
            <option value="">-- Choisir --</option>
            <option value="tentative évasion">Tentative d'évasion</option>
            <option value="meurtre">Meurtre</option>
            <option value="possession objet interdit">Possession d'objet interdit</option>
            <option value="mutinerie">Mutinerie</option>
        </select>

        <label for="sanction" style="color:white; font-weight:bold;">Sanction :</label>
        <input type="text" name="sanction" placeholder="Ex : cellule disciplinaire 2 jours" required style="width:100%; padding: 10px; margin: 10px 0; border-radius: 6px;">

        <button type="submit" class="btn-neon" style="margin-top: 15px;">✅ Enregistrer l'infraction</button>
    </form>
</div>