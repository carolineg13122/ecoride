<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Accès réservé aux employés
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'employe')) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Accès réservé aux employés.'));
    exit;
}

$pseudo = $_SESSION['pseudo'] ?? 'Employé';

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container mt-5">
    <h2>👨‍💼 Bienvenue <?= htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8') ?> dans votre espace employé</h2>
    <p>Depuis cet espace, vous pouvez gérer les avis laissés par les utilisateurs et superviser les trajets.</p>

    <!-- Messages de retour optionnels -->
    <?php if (!empty($_GET['message'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['erreur'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_GET['erreur'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="/views/espace_employe.php" class="btn btn-primary mb-2">🗣 Modérer les avis</a><br>
        <a href="/views/signalements_trajets.php" class="btn btn-warning mb-2">⚠️ Trajets signalés</a><br>
        <a href="/controllers/deconnexion.php" class="btn btn-secondary">🔓 Se déconnecter</a>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
