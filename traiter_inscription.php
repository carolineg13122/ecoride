<?php
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Lire l'image si présente
    $photo_blob = null;
    if (!empty($_FILES['photo']['tmp_name'])) {
        $photo_blob = file_get_contents($_FILES['photo']['tmp_name']);
    }

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: inscription.php?message=Email déjà utilisé.");
        exit();
    }

    // Insertion avec image binaire
    $stmt = $conn->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, photo, credits) VALUES (?, ?, ?, ?, ?, 20)");
    $stmt->bindParam(1, $nom);
    $stmt->bindParam(2, $prenom);
    $stmt->bindParam(3, $email);
    $stmt->bindParam(4, password_hash($mot_de_passe, PASSWORD_DEFAULT));
    $stmt->bindParam(5, $photo_blob, PDO::PARAM_LOB);


    $stmt->execute();

    header("Location: connexion.php?message=Inscription réussie.");
    exit();
}
