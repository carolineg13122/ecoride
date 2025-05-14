<?php
require_once("config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php?message=Veuillez vous connecter.");
    exit();
}

$user_id = $_SESSION['user_id'];
$depart = $_POST['depart'] ?? '';
$adresse_depart = $_POST['adresse_depart'] ?? '';
$destination = $_POST['destination'] ?? '';
$adresse_arrivee = $_POST['adresse_arrivee'] ?? '';
$date = $_POST['date'] ?? '';
$prix = $_POST['prix'] ?? '';
$places = $_POST['places'] ?? '';
$plaque_immatriculation = $_POST['plaque_immatriculation'] ?? '';
$date_immatriculation = $_POST['date_immatriculation'] ?? '';
$duree_minutes = $_POST['duree_minutes'] ?? 0;


// ✅ Vérification des champs
if (empty($depart) || empty($destination) || empty($date) || empty($prix) || empty($places) || empty($plaque_immatriculation) || empty($date_immatriculation)) {
    die("❌ Erreur : Tous les champs doivent être remplis.");
}

// ✅ Insérer le trajet dans la base de données
$sql = "INSERT INTO trajets (chauffeur, depart, adresse_depart, destination, adresse_arrivee, date, prix, places, plaque_immatriculation, date_immatriculation, duree_minutes)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

try {
    $chauffeur_nom = $_SESSION['prenom'] . ' ' . $_SESSION['nom'];
    $stmt->execute([$chauffeur_nom, $depart, $adresse_depart, $destination, $adresse_arrivee, $date, $prix, $places, $plaque_immatriculation, $date_immatriculation, $duree_minutes]);
    header("Location: mes_trajets.php?message=Trajet ajouté avec succès.");
    exit();
} catch (PDOException $e) {
    die("❌ Erreur d'insertion : " . $e->getMessage());
}
