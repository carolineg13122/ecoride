<?php
require_once("../config/database.php");
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php?message=Connectez-vous pour annuler un trajet.");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_GET['id'] ?? null;

if (!$trajet_id) {
    die("❌ Erreur : aucun trajet spécifié.");
}

try {
    // Vérifier que l'utilisateur est bien le chauffeur (user_id)
    $stmt = $conn->prepare("SELECT * FROM trajets WHERE id = :id AND user_id = :chauffeur_id");
    $stmt->execute([
        ':id' => $trajet_id,
        ':chauffeur_id' => $user_id
    ]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        die("❌ Ce trajet n'existe pas ou ne vous appartient pas.");
    }

    $prix = $trajet['prix'];

    // Récupérer tous les passagers du trajet
    $stmt = $conn->prepare("SELECT r.user_id, u.email, u.prenom FROM reservations r
                            JOIN users u ON r.user_id = u.id
                            WHERE r.trajet_id = :trajet_id");
    $stmt->execute([':trajet_id' => $trajet_id]);
    $passagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rembourser les crédits + envoyer mail à chaque passager
    foreach ($passagers as $p) {
        // Crédit
        $stmt_credit = $conn->prepare("UPDATE users SET credits = credits + :prix WHERE id = :user_id");
        $stmt_credit->execute([
            ':prix' => $prix,
            ':user_id' => $p['user_id']
        ]);

        // Mail
        $to = $p['email'];
        $subject = "🚫 Annulation de votre covoiturage";
        $message = "Bonjour " . htmlspecialchars($p['prenom']) . ",\n\n" .
                   "Le trajet de " . htmlspecialchars($trajet['depart']) . " à " . htmlspecialchars($trajet['destination']) .
                   " prévu le " . $trajet['date'] . " a été annulé par le chauffeur.\n" .
                   "Vos crédits ont été automatiquement remboursés.\n\n" .
                   "Merci pour votre compréhension,\nL'équipe EcoRide";
        $headers = "From: noreply@ecoride.com";

        mail($to, $subject, $message, $headers);
    }

    // Supprimer les réservations liées
    $stmt = $conn->prepare("DELETE FROM reservations WHERE trajet_id = :trajet_id");
    $stmt->execute([':trajet_id' => $trajet_id]);

    // Supprimer le trajet
    $stmt = $conn->prepare("DELETE FROM trajets WHERE id = :id");
    $stmt->execute([':id' => $trajet_id]);

    header("Location: ../views/mes_trajets.php?message=Trajet annulé avec succès. Les passagers ont été remboursés et informés.");
    exit();

} catch (PDOException $e) {
    die("❌ Erreur PDO : " . $e->getMessage());
}
