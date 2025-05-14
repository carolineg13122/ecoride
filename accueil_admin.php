<?php
session_start();
require_once("config/database.php");

// Vérifier que l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php?message=Accès réservé aux administrateurs.");
    exit();
}

$nom = $_SESSION['nom'] ?? 'Administrateur';
?>

<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>👑 Bienvenue <?= htmlspecialchars($nom) ?> dans votre espace administrateur</h2>

    <p class="mt-3">Depuis cet espace, vous pouvez :</p>

    <div class="mt-4">
        <a href="gerer_utilisateurs.php" class="btn btn-primary mb-2">👥 Gérer les utilisateurs</a><br>
        <a href="gerer_employes.php" class="btn btn-primary mb-2">🧑‍💼 Gérer les employés</a><br>
        <a href="historique_signalements.php" class="btn btn-primary mb-2">📜 Historique signalements</a><br>
        <a href="statistiques.php" class="btn btn-info mb-2">📈 Voir les statistiques</a><br>
        <a href="deconnexion.php" class="btn btn-secondary">🔓 Se déconnecter</a>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
