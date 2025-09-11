<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /controllers/connexion.php?message=Accès%20réservé%20aux%20administrateurs.");
    exit;
}

$nom = $_SESSION['nom'] ?? 'Administrateur';
?>

<?php require_once __DIR__ . '/../templates/header.php';
 ?>

<div class="container mt-5">
    <h2>👑 Bienvenue <?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?> dans votre espace administrateur</h2>

    <p class="mt-3">Depuis cet espace, vous pouvez :</p>

    <div class="mt-4">
        <a href="/views/gerer_utilisateurs.php" class="btn btn-primary mb-2">👥 Gérer les utilisateurs</a><br>
        <a href="/views/gerer_employes.php" class="btn btn-primary mb-2">🧑‍💼 Gérer les employés</a><br>
        <a href="/views/historique_avis.php" class="btn btn-primary mb-2">📚 Historique des avis</a><br>
        <a href="/views/historique_signalements.php" class="btn btn-primary mb-2">📜 Historique signalements</a><br>
        <a href="/views/statistiques.php" class="btn btn-info mb-2">📈 Voir les statistiques</a><br>
        <a href="/controllers/deconnexion.php" class="btn btn-secondary">🔓 Se déconnecter</a>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
