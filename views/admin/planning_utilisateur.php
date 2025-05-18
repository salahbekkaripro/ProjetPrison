<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/check_role.php';
require_once '../../includes/header.php';

// Vérifie que seul le rôle 'admin' peut accéder
checkRole('admin');
// Sélection de tous les utilisateurs
$users = $pdo->query("SELECT id, nom, prenom FROM users ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$date_du_jour = date("d/m/Y");
$pageTitle = "Planning utilisateur";

$customHeadStyle = <<<CSS

        /* ======= Reset & Base ======= */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #121212;
            color: #eee;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a {
            color: #ffa500;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        /* ======= Container ======= */
        .dashboard-container {
            max-width: 1100px;
            margin: 40px auto 80px;
            background: #1e1e1e;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(255, 165, 0, 0.15);
            display: flex;
            flex-direction: column;
        }

        /* ======= Title ======= */
        .dashboard-container h2 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 30px;
            color: #ffa500;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(255, 165, 0, 0.8);
        }

        /* ======= User Selector ======= */
        .selector {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .selector label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffb347;
            white-space: nowrap;
        }
        .selector select {
            min-width: 220px;
            padding: 10px 14px;
            font-size: 1rem;
            border-radius: 8px;
            border: 1px solid #ffa500;
            background: #2a2a2a;
            color: #fff;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        .selector select:hover, .selector select:focus {
            border-color: #ffcc66;
            outline: none;
        }

        /* ======= Action Buttons ======= */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .btn-print, .btn-pdf {
            background: linear-gradient(45deg, #b34747, #7f1e1e);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 12px 25px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(183, 71, 71, 0.5);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .btn-print:hover, .btn-pdf:hover {
            background: linear-gradient(45deg, #ff704d, #cc4c3f);
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(255, 112, 77, 0.7);
        }
        .btn-print svg, .btn-pdf svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        /* ======= Planning Container ======= */
        #planning-container {
            overflow-x: auto;
            border-radius: 12px;
            background: #2e2e2e;
            padding: 15px;
            box-shadow: inset 0 0 12px #ff9900aa;
            min-height: 120px;
            color: #ddd;
        }

        /* ======= Table ======= */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 700px;
        }
        th, td {
            border: 1px solid #444;
            padding: 9px 12px;
            text-align: center;
            vertical-align: middle;
            color: #eee;
            background-color: #3a3a3a;
            transition: background-color 0.3s ease;
        }
        th {
            background-color: #cc6600;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        tbody tr:hover td {
            background-color: #ffa500;
            color: #222;
            font-weight: 600;
            cursor: default;
        }

        /* ======= Responsive ======= */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 15px;
                margin: 20px 15px 60px;
            }
            #planning-container {
                min-height: 200px;
            }
            table {
                font-size: 11px;
                min-width: 500px;
            }
        }

        /* ======= Print ======= */
        @media print {
            html, body {
                zoom: 75%;
                margin: 0;
                padding: 0;
                background: white !important;
                color: black !important;
            }
            body * {
                visibility: hidden;
            }
            .dashboard-container, .dashboard-container * {
                visibility: visible;
            }
            .dashboard-container {
                position: relative;
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
                background: white;
                padding: 0;
                box-shadow: none;
            }
            button, .selector, nav, header, footer {
                display: none !important;
            }
            table, tr, td, th {
                page-break-inside: avoid;
                break-inside: avoid;
                color: black !important;
                background: white !important;
            }
        }
        #planning-container {
    overflow-x: auto;
    border-radius: 12px;
    padding: 15px;
    box-shadow: inset 0 0 12px #ff9900aa;
    min-height: 120px;
    color: black !important;
}
#planning-container * {
    color: black !important;
}

CSS;
    
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../../includes/head.php'; ?>

<body>
<?php include '../../includes/navbar.php'; ?>

<div class="dashboard-container">
    <h2>📅 Planning d'un utilisateur</h2>

    <div class="selector">
        <label for="user_id">Choisissez un utilisateur :</label>
        <select id="user_id" aria-label="Sélectionner un utilisateur">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="action-buttons" id="buttons" style="display:none;">
        <button onclick="window.print()" class="btn-print" type="button" title="Imprimer le planning">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6 7v4h12V7H6zm12 10H6v-6h12v6zM6 3h12v2H6V3z"/></svg>🖨️ Imprimer
        </button>
        <button onclick="downloadPDF()" class="btn-pdf" type="button" title="Télécharger le planning en PDF">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3C7 3 3 7 3 12s4 9 9 9 9-4 9-9-4-9-9-9z"/></svg>⬇️ Télécharger PDF
        </button>
    </div>

    <div id="planning-container" role="region" aria-live="polite" aria-label="Planning utilisateur"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.querySelector('.dashboard-container');
        const opt = {
            margin: 0.3,
            filename: 'planning_utilisateur.pdf',
            image: { type: 'jpeg', quality: 0.95 },
            html2canvas: { scale: 1.3, scrollY: 0, useCORS: true },
            jsPDF: { unit: 'cm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().from(element).set(opt).save();
    }

    const select = document.getElementById('user_id');
    const container = document.getElementById('planning-container');
    const buttons = document.getElementById('buttons');

    select.addEventListener('change', () => {
        const userId = select.value;
        if (!userId) {
            container.innerHTML = '';
            buttons.style.display = 'none';
            return;
        }

        container.innerHTML = "<p style='color:#ffa500; text-align:center;'>Chargement du planning…</p>";

        fetch('../../ajax/ajax_get_planning.php?user_id=' + encodeURIComponent(userId))
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                buttons.style.display = 'flex';
            })
            .catch(() => {
                container.innerHTML = "<p style='color:#f44336; text-align:center;'>Erreur lors du chargement du planning.</p>";
                buttons.style.display = 'none';
            });
    });
</script>
</body>
</html>
