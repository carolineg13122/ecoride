<?php
require_once("config/database.php");
session_start();
// Debug temporaire
file_put_contents('log.txt', print_r($_POST, true));

// Vérifier si l'utilisateur est un employé
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employe') {
    header("Location: connexion.php");
    exit();
}

$confirmation_id = $_POST['confirmation_id'] ?? null;
$trajet_id = $_POST['trajet_id'] ?? null;
$chauffeur_id = $_POST['chauffeur_id'] ?? null;
$action = $_POST['action'] ?? 'crediter';

if (!$confirmation_id) {
    die("❌ ID confirmation manquant.");
}

// Vérifier si la confirmation est déjà validée
$stmt = $conn->prepare("SELECT valide FROM confirmations WHERE id = ?");
$stmt->execute([$confirmation_id]);
$confirmation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($confirmation && $confirmation['valide'] == 1) {
    $redirect = ($_SESSION['role'] === 'employe') ? 'espace_employe.php' : 'espace_utilisateur.php';
    header("Location: $redirect?signalement=deja_traite");
    exit();
}

if ($action === 'refuser') {
    // Marquer comme rejeté
    $stmt = $conn->prepare("
        UPDATE confirmations 
        SET valide = 1, statut = 'rejete', traite_par = ?, date_validation = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $confirmation_id]);

    header("Location: espace_employe.php?signalement=rejete");
    exit();
}

// Créditer
if (!$trajet_id || !$chauffeur_id) {
    die("❌ Paramètres de crédit manquants.");
}

try {
    // Récupérer le prix du trajet depuis la base (sécurité renforcée)
    $stmt = $conn->prepare("SELECT prix FROM trajets WHERE id = ?");
    $stmt->execute([$trajet_id]);
    $prix = $stmt->fetchColumn();

    if (!$prix) {
        die("❌ Prix du trajet introuvable.");
    }

    // Compter le nombre de passagers
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE trajet_id = ?");
    $stmt->execute([$trajet_id]);
    $nb_passagers = $stmt->fetchColumn();

    $gain = $prix * $nb_passagers;

    // Créditer le chauffeur
    $stmt = $conn->prepare("UPDATE users SET credits = credits + :gain WHERE id = :chauffeur_id");
    $stmt->execute([
        ':gain' => $gain,
        ':chauffeur_id' => $chauffeur_id
    ]);

    // Marquer comme traité
    $stmt = $conn->prepare("
        UPDATE confirmations 
        SET valide = 1, statut = 'ok', traite_par = ?, date_validation = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $confirmation_id]);

    header("Location: espace_employe.php?signalement=ok");
    exit();

} catch (PDOException $e) {
    die("❌ Erreur : " . $e->getMessage());
}
