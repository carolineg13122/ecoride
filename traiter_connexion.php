<?php
require_once("config/database.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    // Recherche de l'utilisateur
    $stmt = $conn->prepare("SELECT id, nom, mot_de_passe, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
        // Création de la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['role'] = $user['role'];

        // Redirection selon le rôle
        switch ($user['role']) {
            case 'admin':
                header("Location: accueil_admin.php");
                break;
            case 'employe':
                header("Location: accueil_employe.php");
                break;
            default:
                header("Location: espace_utilisateur.php?message=Connexion réussie !");
                break;
        
        }
        exit();

    } else {
        header("Location: connexion.php?message=Identifiants incorrects.");
        exit();
    }
}
?>
