<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Connectez-vous pour annuler une réservation.'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /views/mes_reservations.php');
    exit;
}

$user_id        = (int)$_SESSION['user_id'];
$reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;

if ($reservation_id <= 0) {
    header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Aucune réservation spécifiée.'));
    exit;
}

try {
    // Récupérer la réservation (et vérifier la propriété) + statut du trajet
    $stmt = $conn->prepare("
        SELECT r.id, r.user_id, r.trajet_id, t.statut
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Réservation introuvable ou non autorisée.'));
        exit;
    }

    // Empêcher l’annulation si le trajet est déjà en cours ou terminé (optionnel mais recommandé)
    $statut = (string)($reservation['statut'] ?? '');
    if (in_array($statut, ['en_cours', 'termine'], true)) {
        header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Impossible d’annuler un trajet déjà en cours ou terminé.'));
        exit;
    }

    $trajet_id = (int)$reservation['trajet_id'];

    // Transaction: remboursement + libération place + suppression
    $conn->beginTransaction();

    // Supprimer la réservation
    $stmt = $conn->prepare('DELETE FROM reservations WHERE id = ? AND user_id = ?');
    $stmt->execute([$reservation_id, $user_id]);

    // Libérer une place
    $stmt = $conn->prepare('UPDATE trajets SET places = places + 1 WHERE id = ?');
    $stmt->execute([$trajet_id]);

    // Rembourser **+2 crédits** (ta logique d’origine)
    $stmt = $conn->prepare('UPDATE users SET credits = credits + 2 WHERE id = ?');
    $stmt->execute([$user_id]);

    $conn->commit();

    header('Location: /views/mes_reservations.php?message=' . rawurlencode('Réservation annulée.'));
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    // En prod: log $e->getMessage()
    header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Erreur serveur.'));
    exit;
}
