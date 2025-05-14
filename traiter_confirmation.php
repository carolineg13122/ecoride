<?php
require_once("config/database.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_POST['trajet_id'];
$statut = $_POST['statut'];
$commentaire = $_POST['commentaire'] ?? null;
$note = $_POST['note'] ?? null;
$avis = $_POST['avis'] ?? null;

try {
    // 1. Enregistrer la confirmation
    $stmt = $conn->prepare("INSERT INTO confirmations 
        (id_trajet, id_passager, statut, commentaire, note, avis, valide)
        VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([
        $trajet_id,
        $user_id,
        $statut,
        $commentaire,
        $note,
        $avis
    ]);

    // 2. Si le statut est "probleme", on bloque le versement
    if ($statut === 'probleme') {
        header("Location: confirmer_covoiturage.php?message=Votre retour a été transmis. Un employé va examiner votre remarque.");
        exit();
    }

    // 3. Vérifier si tous les passagers ont validé
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE trajet_id = ?");
    $stmt->execute([$trajet_id]);
    $total_passagers = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM confirmations WHERE id_trajet = ? AND statut = 'valide'");
    $stmt->execute([$trajet_id]);
    $total_validations = $stmt->fetchColumn();

    // 4. Si tout le monde a validé, on crédite le chauffeur
    if ($total_passagers == $total_validations) {
        // Récupérer le chauffeur et le prix
        $stmt = $conn->prepare("SELECT user_id, prix FROM trajets WHERE id = ?");
        $stmt->execute([$trajet_id]);
        $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

        $chauffeur_id = $trajet['user_id'];
        $gain = $trajet['prix'] * $total_passagers;

        // Ajouter les crédits au chauffeur
        $stmt = $conn->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        $stmt->execute([$gain, $chauffeur_id]);
    }

    header("Location: confirmer_covoiturage.php?message=Merci pour votre retour !");
    exit();

} catch (PDOException $e) {
    die("❌ Erreur : " . $e->getMessage());
}
