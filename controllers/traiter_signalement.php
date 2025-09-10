<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// --- Accès employé
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employe') {
    header("Location: /controllers/connexion.php");
    exit();
}

// --- CSRF (doit venir de views/signalements_trajets.php)
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_signalements']) 
    || !hash_equals($_SESSION['csrf_signalements'], $_POST['csrf_token'])) {
    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Session expirée, réessayez."));
    exit();
}

$confirmation_id = isset($_POST['confirmation_id']) ? (int)$_POST['confirmation_id'] : 0;
$trajet_id       = isset($_POST['trajet_id'])       ? (int)$_POST['trajet_id']       : 0;
$chauffeur_id    = isset($_POST['chauffeur_id'])    ? (int)$_POST['chauffeur_id']    : 0;
$action          = $_POST['action'] ?? 'crediter';

if ($confirmation_id <= 0) {
    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("ID confirmation manquant."));
    exit();
}

// --- Charger la confirmation et vérifier cohérence
$stmt = $conn->prepare("
    SELECT id, id_trajet, id_passager, statut, valide
    FROM confirmations
    WHERE id = ?
");
$stmt->execute([$confirmation_id]);
$conf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conf) {
    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Confirmation introuvable."));
    exit();
}

// Cohérence confirmation <-> trajet
if ($trajet_id && (int)$conf['id_trajet'] !== $trajet_id) {
    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Incohérence de trajet."));
    exit();
}

// Déjà traitée ?
if ((int)$conf['valide'] === 1) {
    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Signalement déjà traité."));
    exit();
}

try {
    if ($action === 'refuser') {
        // Marquer rejeté
        $stmt = $conn->prepare("
            UPDATE confirmations
            SET valide = 1, statut = 'rejeté_employé', traite_par = ?, date_validation = NOW()
            WHERE id = ?
        ");
        $stmt->execute([ (int)$_SESSION['user_id'], $confirmation_id ]);

        header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Signalement rejeté."));
        exit();
    }

    // --- Créditer quand même
    if ($trajet_id <= 0 || $chauffeur_id <= 0) {
        header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Paramètres de crédit manquants."));
        exit();
    }

    // Lire prix du trajet (source de vérité)
    $stmt = $conn->prepare("SELECT prix FROM trajets WHERE id = ?");
    $stmt->execute([$trajet_id]);
    $prix = $stmt->fetchColumn();

    if ($prix === false) {
        header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Prix du trajet introuvable."));
        exit();
    }

    // Nb passagers (réservations)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE trajet_id = ?");
    $stmt->execute([$trajet_id]);
    $nb_passagers = (int)$stmt->fetchColumn();

    // Si tu préfères ne créditer que les passagers qui ont validé "ok":
    // $stmt = $conn->prepare("SELECT COUNT(*) FROM confirmations WHERE id_trajet = ? AND statut = 'ok'");
    // $stmt->execute([$trajet_id]);
    // $nb_passagers = (int)$stmt->fetchColumn();

    $gain = (float)$prix * $nb_passagers;

    $conn->beginTransaction();

    // Créditer
    $stmt = $conn->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
    $stmt->execute([ $gain, $chauffeur_id ]);

    // Marquer la confirmation traitée (validée par employé)
    $stmt = $conn->prepare("
        UPDATE confirmations
        SET valide = 1, statut = 'validé_employé', traite_par = ?, date_validation = NOW()
        WHERE id = ?
    ");
    $stmt->execute([ (int)$_SESSION['user_id'], $confirmation_id ]);

    $conn->commit();

    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Chauffeur crédité (${gain} crédits)."));
    exit();

} catch (Throwable $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    // En prod: log $e->getMessage()
    header("Location: /views/signalements_trajets.php?message=" . rawurlencode("Erreur serveur, réessayez."));
    exit();
}
