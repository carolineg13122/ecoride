<?php
require_once("../config/database.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$trajet_id || !$action) {
    header("Location: ../views/mes_trajets.php?message=Paramètres manquants.");
    exit();
}

try {
    // Vérification : le trajet appartient bien à l'utilisateur
    $stmt = $conn->prepare("SELECT * FROM trajets WHERE id = ? AND user_id = ?");
    $stmt->execute([$trajet_id, $user_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        header("Location: ../views/mes_trajets.php?message=Trajet introuvable ou non autorisé.");
        exit();
    }

    // Mise à jour du statut
    if ($action === 'demarrer') {
        $stmt = $conn->prepare("UPDATE trajets SET statut = 'en_cours' WHERE id = ?");
        $stmt->execute([$trajet_id]);
        // Récupérer les passagers
$stmt = $conn->prepare("SELECT u.email, u.prenom FROM reservations r
JOIN users u ON r.user_id = u.id
WHERE r.trajet_id = ?");
$stmt->execute([$trajet_id]);
$passagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer l'email
$subject = "📝 Confirmation de votre covoiturage EcoRide";
$message = "Bonjour %prenom%,\n\n" .
"Le trajet auquel vous avez participé est maintenant terminé.\n" .
"Veuillez vous rendre sur votre espace personnel pour confirmer que tout s'est bien passé, " .
"laisser une note ou signaler un problème éventuel.\n\nMerci !\nL'équipe EcoRide";
$headers = "From: noreply@ecoride.com";

// Envoyer l'email à chaque passager
foreach ($passagers as $p) {
$to = $p['email'];
$body = str_replace('%prenom%', $p['prenom'], $message);
mail($to, $subject, $body, $headers);
}

        header("Location: ../views/mes_trajets.php?message=Trajet démarré !");
    } elseif ($action === 'terminer') {
        $stmt = $conn->prepare("UPDATE trajets SET statut = 'termine' WHERE id = ?");
        $stmt->execute([$trajet_id]);

        // 🚧 Tu pourras ici appeler une fonction pour envoyer un mail aux passagers
        // (on fera cette partie ensuite)

        header("Location: ../views/mes_trajets.php?message=Trajet terminé. Les passagers vont recevoir une notification.");
    } else {
        header("Location: ../views/mes_trajets.php?message=Action inconnue.");
    }
} catch (PDOException $e) {
    die("❌ Erreur : " . $e->getMessage());
}
