<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// --- Accès : utilisateur connecté + rôle utilisateur
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'utilisateur') {
    header("Location: /controllers/connexion.php");
    exit();
}

$user_id  = (int)$_SESSION['user_id'];
$trajet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trajet_id <= 0) {
    header("Location: /views/espace_utilisateur.php?erreur=id_manquant");
    exit();
}

// Vérifier que l'utilisateur a bien une réservation sur ce trajet
$stmt = $conn->prepare("SELECT 1 FROM reservations WHERE trajet_id = ? AND user_id = ?");
$stmt->execute([$trajet_id, $user_id]);
$a_bien_reserve = (bool)$stmt->fetchColumn();

if (!$a_bien_reserve) {
    header("Location: /views/espace_utilisateur.php?erreur=" . rawurlencode("Vous n'avez pas réservé ce trajet."));
    exit();
}

// Déjà signalé ? (on ne compte que les confirmations avec statut 'probleme')
$stmt = $conn->prepare("SELECT COUNT(*) FROM confirmations WHERE id_trajet = ? AND id_passager = ? AND statut = 'probleme'");
$stmt->execute([$trajet_id, $user_id]);
$deja_signale = $stmt->fetchColumn() > 0;

// CSRF token pour la soumission
if (empty($_SESSION['csrf_signal'])) {
    $_SESSION['csrf_signal'] = bin2hex(random_bytes(32));
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF
    $csrf_ok = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_signal'], $_POST['csrf_token']);
    if (!$csrf_ok) {
        $erreur = "Session expirée. Merci de réessayer.";
    } elseif ($deja_signale) {
        $erreur = "⚠️ Vous avez déjà signalé ce trajet. Il est en cours de traitement.";
    } else {
        $commentaire = trim($_POST['commentaire'] ?? '');
        if ($commentaire === '') {
            $erreur = "Le commentaire est obligatoire.";
        } elseif (mb_strlen($commentaire) > 2000) {
            $erreur = "Le commentaire est trop long (2000 caractères max).";
        } else {
            // Enregistrer le signalement
            $stmt = $conn->prepare("
                INSERT INTO confirmations (id_trajet, id_passager, commentaire, statut, valide, created_at)
                VALUES (?, ?, ?, 'probleme', 0, NOW())
            ");
            $stmt->execute([$trajet_id, $user_id, $commentaire]);

            // Empêcher double POST
            unset($_SESSION['csrf_signal']);

            header("Location: /views/espace_utilisateur.php?signalement=envoye");
            exit();
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container mt-5">
    <h2>❗ Signaler un problème sur ce trajet</h2>

    <?php if ($deja_signale): ?>
        <div class="alert alert-warning">⚠️ Vous avez déjà signalé ce trajet. Il est en cours de traitement.</div>
    <?php endif; ?>

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$deja_signale): ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_signal'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label for="commentaire" class="form-label">Expliquez ce qui s’est mal passé :</label>
                <textarea name="commentaire" id="commentaire" class="form-control" rows="5" required></textarea>
                <small class="form-text text-muted">2000 caractères max.</small>
            </div>
            <button type="submit" class="btn btn-danger">🚨 Envoyer le signalement</button>
            <a href="/views/espace_utilisateur.php" class="btn btn-secondary ms-2">Retour</a>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
