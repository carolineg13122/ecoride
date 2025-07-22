<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php?message=Vous n'êtes pas connecté.");
    exit();
}

require_once("../templates/header.php");
?>

<div class="container mt-5 text-center">
    <h2>🔐 Déconnexion</h2>
    <p>Êtes-vous sûr de vouloir vous déconnecter ?</p>

    <form action="../controllers/deconnexion.php" method="POST" class="d-inline">
        <button type="submit" class="btn btn-danger">✅ Oui, me déconnecter</button>
    </form>

    <a href="../index.php" class="btn btn-secondary ml-2">↩️ Non, retourner à l’accueil</a>
</div>

<?php require_once("../templates/footer.php"); ?>
