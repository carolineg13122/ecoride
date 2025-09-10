<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /controllers/connexion.php?message=" . rawurlencode("Veuillez vous connecter."));
    exit;
}

$user_id   = (int) $_SESSION['user_id'];
$trajet_id = isset($_POST['trajet_id']) ? (int)$_POST['trajet_id'] : 0;
$statut    = trim($_POST['statut'] ?? ''); // attendu: 'ok' ou 'probleme'
$comment   = trim($_POST['commentaire'] ?? '');
$note      = $_POST['note'] ?? null;
$avis      = trim($_POST['avis'] ?? '');

// Normalisation / validation légère
$statut = in_array($statut, ['ok','probleme'], true) ? $statut : 'ok';
$note   = ($note !== null && $note !== '') ? (int)$note : null;
if ($note !== null && ($note < 1 || $note > 5)) { $note = null; }

try {
    // 1) Vérifier que l'utilisateur a bien une réservation sur ce trajet
    $stmt = $conn->prepare("SELECT 1 FROM reservations WHERE trajet_id = ? AND user_id = ?");
    $stmt->execute([$trajet_id, $user_id]);
    if (!$stmt->fetchColumn()) {
        header("Location: /views/mes_reservations.php?message=" . rawurlencode("Réservation introuvable."));
        exit;
    }

    // 2) Transaction pour éviter les courses conditions
    $conn->beginTransaction();

    // 2.a) Upsert confirmation (évite les doublons)
    // Si tu as une contrainte UNIQUE (id_trajet, id_passager), tu peux faire un ON DUPLICATE KEY UPDATE.
    // Sinon on fait un SELECT puis INSERT/UPDATE.
    $stmt = $conn->prepare("SELECT id FROM confirmations WHERE id_trajet = ? AND id_passager = ?");
    $stmt->execute([$trajet_id, $user_id]);
    $confirm_id = $stmt->fetchColumn();

    $valide = ($statut === 'ok') ? 1 : 0;

    if ($confirm_id) {
        $stmt = $conn->prepare("
            UPDATE confirmations
            SET statut = ?, commentaire = ?, note = ?, avis = ?, valide = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$statut, $comment ?: null, $note, $avis ?: null, $valide, $confirm_id]);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO confirmations (id_trajet, id_passager, statut, commentaire, note, avis, valide, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$trajet_id, $user_id, $statut, $comment ?: null, $note, $avis ?: null, $valide]);
    }

    // 2.b) Si problème, on s'arrête là (pas de versement)
    if ($statut === 'probleme') {
        $conn->commit();
        header("Location: /views/mes_reservations.php?message=" . rawurlencode("Votre retour a été transmis. Un employé va examiner votre remarque."));
        exit;
    }

    // 2.c) Si OK, vérifier si tous les passagers ont validé 'ok'
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE trajet_id = ?");
    $stmt->execute([$trajet_id]);
    $total_passagers = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM confirmations WHERE id_trajet = ? AND statut = 'ok'");
    $stmt->execute([$trajet_id]);
    $total_valid_ok = (int)$stmt->fetchColumn();

    if ($total_passagers > 0 && $total_passagers === $total_valid_ok) {
        // 2.d) Créditer le chauffeur une seule fois (au dernier OK)
        $stmt = $conn->prepare("SELECT user_id, prix FROM trajets WHERE id = ?");
        $stmt->execute([$trajet_id]);
        $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($trajet) {
            $chauffeur_id = (int)$trajet['user_id'];
            $gain         = (float)$trajet['prix'] * $total_passagers;

            // Crédit chauffeur
            $stmt = $conn->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$gain, $chauffeur_id]);
        }
    }

    $conn->commit();

    header("Location: /views/mes_reservations.php?message=" . rawurlencode("Merci pour votre retour !"));
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header("Location: /views/mes_reservations.php?message=" . rawurlencode("Erreur serveur, réessayez.")); 
    exit;
}
