<?php
require_once("config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
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
    $vehicule_id = !empty($_POST['vehicule_id']) ? $_POST['vehicule_id'] : null;

    // Récupérer le nom et prénom de l'utilisateur pour enregistrer le chauffeur
    $stmt_user = $conn->prepare("SELECT prenom, nom, credits FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['credits'] < 2) {
        header("Location: mes_trajets.php?message=Vous n'avez pas assez de crédits pour publier un trajet.");
        exit();
    }

    $chauffeur = $user['prenom'] . ' ' . $user['nom'];

    // Si aucun véhicule existant, ajouter un nouveau
    if (!$vehicule_id) {
        $marque = $_POST['marque'] ?? '';
        $modele = $_POST['modele'] ?? '';
        $energie = $_POST['energie'] ?? '';
        $plaque = $_POST['plaque_immatriculation'] ?? '';
        $date_immatriculation = $_POST['date_immatriculation'] ?? '';

        $sql_vehicule = "INSERT INTO vehicules (user_id, marque, modele, energie, plaque_immatriculation, date_immatriculation)
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_vehicule = $conn->prepare($sql_vehicule);
        $stmt_vehicule->execute([$user_id, $marque, $modele, $energie, $plaque, $date_immatriculation]);

        $vehicule_id = $conn->lastInsertId();
    }

    // Insertion du trajet avec user_id correctement enregistré
    $sql_trajet = "INSERT INTO trajets (user_id, chauffeur, vehicule_id, depart, adresse_depart, destination, adresse_arrivee, date, prix, places, preferences, fumeur, eco, duree_minutes)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_trajet = $conn->prepare($sql_trajet);
    $stmt_trajet->execute([
        $user_id,
        $chauffeur,
        $vehicule_id,
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
    ]);

    // Mise à jour des crédits utilisateur
    $stmt_credit = $conn->prepare("UPDATE users SET credits = credits - 2 WHERE id = ?");
    $stmt_credit->execute([$user_id]);

    header("Location: mes_trajets.php?message=Trajet publié avec succès !");
    exit();
} else {
    header("Location: publier_trajet.php");
    exit();
}