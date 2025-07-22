<?php 
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php?message=Veuillez vous connecter pour réserver un trajet.");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_POST['trajet_id'] ?? null;

if (!$trajet_id) {
    die("❌ Erreur : aucun identifiant de trajet transmis.");
}

try {
    // Vérifier que le trajet existe et a des places disponibles
    $stmt = $conn->prepare("SELECT places FROM trajets WHERE id = ?");
    $stmt->execute([$trajet_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        die("❌ Erreur : ce trajet n'existe pas.");
    }

    if ($trajet['places'] <= 0) {
        die("❌ Erreur : plus de places disponibles pour ce trajet.");
    }

    // Vérifier que l'utilisateur n'a pas déjà réservé ce trajet
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND trajet_id = ?");
    $stmt->execute([$user_id, $trajet_id]);
    $dejaReserve = $stmt->fetchColumn();

    if ($dejaReserve > 0) {
        die("❌ Vous avez déjà réservé ce trajet.");
    }

    // Vérifier que l'utilisateur a au moins 2 crédits
    $stmt = $conn->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $credits = $stmt->fetchColumn();

    if ($credits < 2) {
        die("❌ Vous n'avez pas assez de crédits pour réserver ce trajet.");
    }

    // Insérer la réservation
    $stmt = $conn->prepare("INSERT INTO reservations (user_id, trajet_id, date_reservation) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $trajet_id]);

    // Décrémenter les places restantes
    $stmt = $conn->prepare("UPDATE trajets SET places = places - 1 WHERE id = ?");
    $stmt->execute([$trajet_id]);

    // Déduire 2 crédits à l'utilisateur
    $stmt = $conn->prepare("UPDATE users SET credits = credits - 2 WHERE id = ?");
    $stmt->execute([$user_id]);

    // 🔔 Simulation d’envoi d’e-mail au chauffeur
    $stmt = $conn->prepare("SELECT u.email, u.prenom, u.nom FROM trajets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?");
    $stmt->execute([$trajet_id]);
    $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($chauffeur && !empty($chauffeur['email'])) {
    $to = $chauffeur['email'];
    $subject = "🚗 Nouvelle réservation sur EcoRide";
    $message = "Bonjour " . htmlspecialchars($chauffeur['prenom']) . " " . htmlspecialchars($chauffeur['nom']) . ",\n\n";
    $message .= "Un passager vient de réserver une place pour votre trajet #" . $trajet_id . ".\n";
    $message .= "Connectez-vous à votre compte pour consulter les détails.\n\n";
    $message .= "Merci d'utiliser EcoRide 🚀";
    $headers = "From: contact@ecoride.fr\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8";

    // Envoi réel si serveur configuré, sinon simulation pour l'ECF
    if (mail($to, $subject, $message, $headers)) {
    $info = "✉️ Un e-mail a été envoyé au chauffeur.";
    } else {
    $info = "✅ Réservation enregistrée. (e-mail non envoyé en local)";
    }
    } else {
    $info = "✅ Réservation enregistrée. (aucune adresse e-mail disponible)";
    }


    // ✅ Redirection
    header("Location: ../views/mes_reservations.php?message=" . urlencode($info));
    exit();

} catch (PDOException $e) {
    die("❌ Erreur lors de la réservation : " . $e->getMessage());
}
