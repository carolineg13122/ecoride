<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php');
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$trajet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trajet_id <= 0) {
    $redirect = (($_SESSION['role'] ?? '') === 'employe') ? '/views/espace_employe.php' : '/views/espace_utilisateur.php';
    header('Location: ' . $redirect . '?erreur=' . rawurlencode('id_manquant'));
    exit;
}

try {
    // Vérifier que l'utilisateur a une réservation sur ce trajet
    $stmt = $conn->prepare("
        SELECT t.id, t.statut
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        WHERE r.user_id = ? AND r.trajet_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $trajet_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $redirect = (($_SESSION['role'] ?? '') === 'employe') ? '/views/espace_employe.php' : '/views/espace_utilisateur.php';
        header('Location: ' . $redirect . '?erreur=' . rawurlencode("reservation_introuvable"));
        exit;
    }

    if (($row['statut'] ?? '') !== 'termine') {
        $redirect = (($_SESSION['role'] ?? '') === 'employe') ? '/views/espace_employe.php' : '/views/espace_utilisateur.php';
        header('Location: ' . $redirect . '?erreur=' . rawurlencode("trajet_non_termine"));
        exit;
    }

    // Récupérer un éventuel avis/note (si formulaire POST)
    $note = isset($_POST['note']) ? (int)$_POST['note'] : null;
    $avis = isset($_POST['avis']) ? trim($_POST['avis']) : null;

    // Anti-doublon : existe déjà ?
    $stmt = $conn->prepare('SELECT id FROM confirmations WHERE id_trajet = ? AND id_passager = ?');
    $stmt->execute([$trajet_id, $user_id]);
    $exist = $stmt->fetchColumn();

    if ($exist) {
        $upd = $conn->prepare("
            UPDATE confirmations 
            SET statut = 'valide', valide = 1, note = :note, avis = :avis, date_validation = NOW() 
            WHERE id_trajet = :trajet AND id_passager = :passager
        ");
        $upd->execute([
            ':note'     => $note,
            ':avis'     => $avis,
            ':trajet'   => $trajet_id,
            ':passager' => $user_id,
        ]);
    } else {
        $ins = $conn->prepare("
            INSERT INTO confirmations (id_trajet, id_passager, statut, valide, note, avis, date_validation) 
            VALUES (:trajet, :passager, 'valide', 1, :note, :avis, NOW())
        ");
        $ins->execute([
            ':trajet'   => $trajet_id,
            ':passager' => $user_id,
            ':note'     => $note,
            ':avis'     => $avis,
        ]);
    }

    $redirect = (($_SESSION['role'] ?? '') === 'employe') ? '/views/espace_employe.php' : '/views/espace_utilisateur.php';
    header('Location: ' . $redirect . '?confirmation=ok');
    exit;

} catch (Throwable $e) {
    $redirect = (($_SESSION['role'] ?? '') === 'employe') ? '/views/espace_employe.php' : '/views/espace_utilisateur.php';
    header('Location: ' . $redirect . '?erreur=' . rawurlencode('Erreur serveur.'));
    exit;
}
