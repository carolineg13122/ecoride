<?php
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $trajet_id = $_POST['trajet_id'];

    $depart = $_POST['depart'] ?? '';
    $adresse_depart = $_POST['adresse_depart'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $adresse_arrivee = $_POST['adresse_arrivee'] ?? '';
    $date = $_POST['date'] ?? '';
    $prix = $_POST['prix'] ?? '';
    $places = $_POST['places'] ?? '';
    $preferences = $_POST['preferences'] ?? '';
    $fumeur = $_POST['fumeur'] ?? 0;
    $eco = $_POST['eco'] ?? 0;
    $duree_minutes = $_POST['duree_minutes'] ?? 0;

    $sql = "UPDATE trajets SET depart = ?, adresse_depart = ?, destination = ?, adresse_arrivee = ?, date = ?, prix = ?, places = ?, preferences = ?, fumeur = ?, eco = ?, duree_minutes = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $depart,
        $adresse_depart,
        $destination,
        $adresse_arrivee,
        $date,
        $prix,
        $places,
        $preferences,
        $fumeur,
        $eco,
        $duree_minutes,
        $trajet_id,
        $user_id
    ]);

    header("Location: /views/mes_trajets.php?message=Trajet modifié avec succès !");
    exit();
} else {
    header("Location: /views/mes_trajets.php");
    exit();
}
