<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'chef'])) {
    echo "Accès interdit.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prisonnier_id'])) {
    $prisonnier_id = intval($_POST['prisonnier_id']);
    $utilisateur_id = $_SESSION['user']['id'];

    // 🔎 Trouver le admin_id associé à l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE utilisateur_id = ?");
    $stmt->execute([$utilisateur_id]);
    $admin_id = $stmt->fetchColumn();

    if (!$admin_id) {
        echo "⛔ Impossible de trouver l'admin correspondant à cet utilisateur.";
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO surveillance (prisonnier_id, admin_id, date_debut, date_fin)
                               VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR))");
        $stmt->execute([$prisonnier_id, $admin_id]);

        header("Location: /ProjetPrison/views/admin/surveillance_cellule.php?success=1");
        exit;
    } catch (PDOException $e) {
        echo "Erreur SQL : " . $e->getMessage();
        exit;
    }
} else {
    echo "Requête invalide.";
    exit;
}
