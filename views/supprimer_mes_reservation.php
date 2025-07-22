<?php
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php?message=Veuillez vous connecter.");
    exit();
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_GET['id'] ?? null;

if (!$reservation_id) {
    die("Réservation non spécifiée.");
}

// Supprimer la réservation
$sql = "DELETE FROM reservations WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$reservation_id, $user_id]);

header("Location: ../views/mes_reservations.php?message=Réservation annulée avec succès.");
exit();
