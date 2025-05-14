<?php
require_once("config/database.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php?message=Connectez-vous pour annuler une réservation.");
    exit();
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_POST['reservation_id'] ?? null;

if (!$reservation_id) {
    die("❌ Erreur : aucune réservation spécifiée.");
}

try {
    // Récupérer la réservation (et vérifier que l'utilisateur est bien le propriétaire)
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $reservation_id,
        ':user_id' => $user_id
    ]);

    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        die("❌ Réservation non trouvée ou non autorisée.");
    }

    $trajet_id = $reservation['trajet_id'];

    // Supprimer la réservation
    $stmt = $conn->prepare("DELETE FROM reservations WHERE id = :id");
    $stmt->execute([':id' => $reservation_id]);

    // Remettre une place dans le trajet
    $stmt = $conn->prepare("UPDATE trajets SET places = places + 1 WHERE id = :trajet_id");
    $stmt->execute([':trajet_id' => $trajet_id]);
    
    // Rembourser 2 crédits à l'utilisateur
    $stmt = $conn->prepare("UPDATE users SET credits = credits + 2 WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);


    // Redirection avec succès
    header("Location: mes_reservations.php?message=Réservation annulée.");
    exit();

} catch (PDOException $e) {
    die("❌ Erreur : " . $e->getMessage());
}
