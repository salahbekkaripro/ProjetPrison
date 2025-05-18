<?php
function checkRole($roles_autorises) {
    if (!isset($_SESSION['user']['role'])) {
        showAccessDenied();
    }

    if (is_string($roles_autorises)) {
        $roles_autorises = [$roles_autorises];
    }

    if (!in_array($_SESSION['user']['role'], $roles_autorises)) {
        showAccessDenied();
    }
}

function showAccessDenied() {
    http_response_code(403);

    $customHeadStyle = <<<CSS
            /* Reset & base */
            body, html {
                margin: 0; padding: 0;
                height: 100%;
                overflow: hidden;
                font-family: 'Courier New', Courier, monospace;
                background: #0b0b0b;
                color: #f5c518;
                user-select: none;
            }

            /* Barreaux animés */
            .bars {
                position: fixed;
                top: 0; left: 50%;
                width: 100vw;
                height: 100vh;
                pointer-events: none;
                transform: translateX(-50%);
                display: flex;
                gap: 20px;
                z-index: 9998;
            }
            .bars div {
                width: 6px;
                height: 100%;
                background: repeating-linear-gradient(
                    to bottom,
                    #222,
                    #222 15px,
                    #444 15px,
                    #444 20px
                );
                animation: pulseBar 2.5s ease-in-out infinite;
                opacity: 0.85;
            }
            /* Animation décalée sur chaque barre */
            .bars div:nth-child(odd) {
                animation-delay: 0s;
            }
            .bars div:nth-child(even) {
                animation-delay: 1.25s;
            }

            @keyframes pulseBar {
                0%, 100% { opacity: 0.85; }
                50% { opacity: 0.4; }
            }

            /* Overlay centré */
            .overlay {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(11,11,11,0.95);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                padding: 20px;
                z-index: 9999;
                animation: fadeInOverlay 0.8s ease forwards;
            }

            @keyframes fadeInOverlay {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            /* Texte principal animé (pulse) */
            h1 {
                font-size: 4rem;
                margin-bottom: 20px;
                text-shadow:
                    0 0 10px #f5c518,
                    0 0 20px #f5c518,
                    0 0 30px #ffcc00,
                    0 0 40px #ffcc00;
                animation: pulseText 2.5s ease-in-out infinite;
            }
            @keyframes pulseText {
                0%, 100% {
                    text-shadow:
                        0 0 10px #f5c518,
                        0 0 20px #f5c518,
                        0 0 30px #ffcc00,
                        0 0 40px #ffcc00;
                    color: #f5c518;
                }
                50% {
                    text-shadow: none;
                    color: #b8860b;
                }
            }

            p {
                font-size: 1.8rem;
                margin-bottom: 40px;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-shadow: 0 0 8px #d4af37cc;
                color: #ffd700cc;
            }

            /* Bouton stylé et animé */
            .btn-prison {
                background: linear-gradient(135deg, #5c3317 0%, #a0522d 100%);
                border: 3px solid #f5c518;
                border-radius: 14px;
                padding: 18px 50px;
                font-size: 1.3rem;
                font-weight: 900;
                color: #fff4b8;
                cursor: pointer;
                text-transform: uppercase;
                box-shadow:
                    0 0 20px #f5c518,
                    inset 0 0 15px #f5c518;
                transition: all 0.3s ease;
                animation: pulseButton 3s ease-in-out infinite;
            }
            .btn-prison:hover {
                background: linear-gradient(135deg, #d4af37 0%, #ffdb58 100%);
                color: #1a1a1a;
                box-shadow:
                    0 0 30px #ffd700,
                    inset 0 0 25px #ffd700;
                transform: scale(1.1);
            }

            @keyframes pulseButton {
                0%, 100% {
                    box-shadow:
                        0 0 20px #f5c518,
                        inset 0 0 15px #f5c518;
                }
                50% {
                    box-shadow:
                        0 0 8px #d4af37,
                        inset 0 0 8px #d4af37;
                }
            }
CSS;
    
    
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <?php include '../includes/head.php'; ?>
    <body>
        <div class="bars">
            <div></div><div></div><div></div><div></div><div></div><div></div>
            <div></div><div></div><div></div><div></div><div></div><div></div>
        </div>
        <div class="overlay" role="alert" aria-live="assertive">
            <h1>Ah non non pas ici !</h1>
            <p>Retourne là-bas avant que ça se corse...</p>
            <button class="btn-prison" onclick="window.history.back()">Retour à la page précédente</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}
