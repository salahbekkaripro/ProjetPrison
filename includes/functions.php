<?php
function envoyer_annonce_generale(PDO $pdo, string $message) {
    // ⚠️ Remplace ici par l'ID du user 'system'
    $system_id = 1;

    $stmt = $pdo->query("SELECT id FROM users WHERE is_banned = 0");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $now = date('Y-m-d H:i:s');

    $insert = $pdo->prepare("
        INSERT INTO notifications (recipient_id, sender_id, type, message, is_read, created_at)
        VALUES (?, ?, 'annonce_generale', ?, 0, ?)
    ");

    foreach ($utilisateurs as $user_id) {
        $insert->execute([$user_id, $system_id, $message, $now]);
    }
}
function nettoyerSanctionsExpirées(PDO $pdo): int {
    $stmt = $pdo->prepare("
        DELETE FROM sanction 
        WHERE type_sanction = 'mise_au_trou' 
          AND fin_sanction IS NOT NULL 
          AND fin_sanction < NOW()
    ");
    $stmt->execute();
    return $stmt->rowCount(); // nombre de sanctions supprimées
}

function verifierSanctionCachot(PDO $pdo) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
        return;
    }

    $utilisateurId = $_SESSION['user']['id'];

    // Récupérer le prisonnier lié
    $stmt = $pdo->prepare("SELECT id FROM prisonnier WHERE utilisateur_id = ?");
    $stmt->execute([$utilisateurId]);
    $prisonnierId = $stmt->fetchColumn();

    if (!$prisonnierId) {
        return; // Aucun prisonnier lié, rien à faire
    }

    // 🧹 Nettoyage des sanctions expirées pour ce prisonnier
    $stmt = $pdo->prepare("
        DELETE FROM sanction 
        WHERE prisonnier_id = ? 
          AND type_sanction = 'mise_au_trou' 
          AND fin_sanction IS NOT NULL 
          AND fin_sanction < NOW()
    ");
    $stmt->execute([$prisonnierId]);

    // 🔒 Vérification s’il reste une sanction active
    $stmt = $pdo->prepare("
        SELECT id 
        FROM sanction 
        WHERE prisonnier_id = ? 
          AND type_sanction = 'mise_au_trou' 
          AND fin_sanction IS NOT NULL 
          AND fin_sanction > NOW()
        LIMIT 1
    ");
    $stmt->execute([$prisonnierId]);

    if ($stmt->fetch()) {
        // Sanction encore active → redirection
        header("Location: /ProjetPrison/views/prisonnier/cachot.php");
        exit;
    }
}
function redirigerSiAuCachot(PDO $pdo) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'prisonnier') {
        return;
    }

    $utilisateurId = $_SESSION['user']['id'];

    // Récupère le prisonnier lié
    $stmt = $pdo->prepare("SELECT id FROM prisonnier WHERE utilisateur_id = ?");
    $stmt->execute([$utilisateurId]);
    $prisonnierId = $stmt->fetchColumn();

    if (!$prisonnierId) return;

    // Supprime les sanctions expirées
    $stmt = $pdo->prepare("
        DELETE FROM sanction
        WHERE prisonnier_id = ?
          AND type_sanction = 'mise_au_trou'
          AND fin_sanction IS NOT NULL
          AND fin_sanction < NOW()
    ");
    $stmt->execute([$prisonnierId]);

    // Vérifie s’il reste une sanction active
    $stmt = $pdo->prepare("
        SELECT id FROM sanction
        WHERE prisonnier_id = ?
          AND type_sanction = 'mise_au_trou'
          AND fin_sanction IS NOT NULL
          AND fin_sanction > NOW()
        LIMIT 1
    ");
    $stmt->execute([$prisonnierId]);

    if ($stmt->fetch()) {
        // Redirection unique
        header("Location: /ProjetPrison/views/prisonnier/cachot.php");
        exit;
    }
}


if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'prisonnier') {
    require_once 'functions.php'; // si ce n'est pas déjà inclus
    redirigerSiAuCachot($pdo);
}





function is_admin_logged_in(): bool {
    return isset($_SESSION['admin']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: ../admin/login.php');
        exit;
    }
}

function is_user_logged_in(): bool {
    return isset($_SESSION['user']);
}

function require_user_login() {
    if (!is_user_logged_in()) {
        header('Location: /ProjetPrison/login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function showOverlayRedirect($message, $redirectUrl, $forceMessage = null, $sound = 'login.mp3') {
    if ($forceMessage !== null) {
        $_SESSION['overlay_force_message'] = $forceMessage;
    }
    $_SESSION['overlay_redirect'] = $redirectUrl;
    $_SESSION['overlay_sound'] = $sound;
    header("Location: overlay_redirect.php");
    exit;
}


function verifier_degradation_sante(PDO $pdo, int $prisonnier_id): ?string {
    $stmt = $pdo->prepare("SELECT etat, derniere_maj_etat FROM prisonnier WHERE id = ?");
    $stmt->execute([$prisonnier_id]);
    $data = $stmt->fetch();

    if (!$data) return null;

    $etatActuel = $data['etat'];
    $dateMaj = $data['derniere_maj_etat'];

    if (!$dateMaj || $etatActuel === 'décédé') return null;

    $jours = (new DateTime())->diff(new DateTime($dateMaj))->days;
    if ($jours < 7) return null;

    $nextEtat = match ($etatActuel) {
        'sain' => 'malade',
        'malade' => 'blessé',
        'blessé' => 'décédé',
        default => null
    };

    if ($nextEtat) {
        $pdo->prepare("UPDATE prisonnier SET etat = ?, derniere_maj_etat = CURDATE() WHERE id = ?")
            ->execute([$nextEtat, $prisonnier_id]);
        return $nextEtat;
    }

    return null;
}
