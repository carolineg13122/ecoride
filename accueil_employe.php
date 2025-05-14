<?php
require_once("config/database.php");
session_start();

// Vérifie que l'utilisateur est un employé
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header("Location: connexion.php?message=Accès réservé aux employés.");
    exit();
}

$pseudo = $_SESSION['pseudo'] ?? 'Employé';
?>

<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>👨‍💼 Bienvenue <?= htmlspecialchars($pseudo) ?> dans votre espace employé</h2>
    <p>Depuis cet espace, vous pouvez gérer les avis laissés par les utilisateurs et superviser les trajets.</p>

    <div class="mt-4">
        <a href="espace_employe.php" class="btn btn-primary mb-2">🗣 Modérer les avis</a><br>

        <!-- Optionnel : lien vers une page pour gérer les trajets problématiques (US11) -->
        <a href="signalements_trajets.php" class="btn btn-warning mb-2">⚠️ Trajets signalés</a><br>

        <a href="deconnexion.php" class="btn btn-secondary">🔓 Se déconnecter</a>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
