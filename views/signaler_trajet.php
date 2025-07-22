<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'utilisateur') {
    header("Location: ../controllers/connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_GET['id'] ?? null;

if (!$trajet_id) {
    header("Location: espace_utilisateur.php?erreur=id_manquant");
    exit();
}

// Vérifier s'il a déjà signalé ce trajet
$stmt = $conn->prepare("SELECT COUNT(*) FROM confirmations WHERE id_trajet = ? AND id_passager = ?");
$stmt->execute([$trajet_id, $user_id]);
$deja_signale = $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($deja_signale) {
        $erreur = "⚠️ Vous avez déjà signalé ce trajet.";
    } else {
        $commentaire = trim($_POST['commentaire'] ?? '');

        if (empty($commentaire)) {
            $erreur = "Le commentaire est obligatoire.";
        } else {
            // Enregistrer le signalement
            $stmt = $conn->prepare("
                INSERT INTO confirmations (id_trajet, id_passager, commentaire, statut, valide)
                VALUES (?, ?, ?, 'probleme', 0)
            ");
            $stmt->execute([$trajet_id, $user_id, $commentaire]);

            header("Location: ../views/espace_utilisateur.php?signalement=envoye");
            exit();
        }
    }
}
?>

<?php include '../templates/header.php'; ?>

<div class="container mt-5">
    <h2>❗ Signaler un problème sur ce trajet</h2>

    <?php if ($deja_signale): ?>
        <div class="alert alert-warning">⚠️ Vous avez déjà signalé ce trajet. Il est en cours de traitement.</div>
    <?php endif; ?>

    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if (!$deja_signale): ?>
    <form method="POST">
        <div class="mb-3">
            <label for="commentaire" class="form-label">Expliquez ce qui s’est mal passé :</label>
            <textarea name="commentaire" id="commentaire" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-danger">🚨 Envoyer le signalement</button>
        <a href="espace_utilisateur.php" class="btn btn-secondary ms-2">Retour</a>
    </form>
    <?php endif; ?>
</div>

<?php include '../templates/footer.php'; ?>
