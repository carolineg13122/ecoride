<?php
session_start();
require_once("../config/database.php");


if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php?message=Connectez-vous pour laisser un avis.");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_GET['id'] ?? null;

if (!$trajet_id) {
    die("❌ Aucun trajet sélectionné.");
}

// Vérifier si un avis existe déjà pour ce trajet
$stmt = $conn->prepare("SELECT COUNT(*) FROM avis WHERE utilisateur_id = ? AND trajet_id = ?");
$stmt->execute([$user_id, $trajet_id]);
$dejaAvis = $stmt->fetchColumn();

if ($dejaAvis > 0) {
    die("❌ Vous avez déjà laissé un avis pour ce trajet.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = $_POST['note'] ?? null;
    $commentaire = $_POST['commentaire'] ?? '';

    if (!$note || $note < 1 || $note > 5) {
        $erreur = "La note doit être entre 1 et 5.";
    } else {
        $stmt = $conn->prepare("INSERT INTO avis (utilisateur_id, trajet_id, note, commentaire, valide, created_at)
                                VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$user_id, $trajet_id, $note, $commentaire]);
        header("Location: ../views/mes_reservations.php?message=Avis envoyé et en attente de validation.");
        exit();
    }
}
?>

<?php require_once("../templates/header.php"); ?>

<div class="container mt-5">
    <h2>🗣 Laisser un avis</h2>

    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="note">Note (de 1 à 5) ⭐</label>
            <select name="note" id="note" class="form-control" required>
                <option value="">Choisissez une note</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= isset($note) && $note == $i ? 'selected' : '' ?>>
                    <?= $i ?> ⭐
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="commentaire">Commentaire</label>
            <textarea name="commentaire" id="commentaire" class="form-control" rows="4" placeholder="Partagez votre expérience..."><?= isset($commentaire) ? htmlspecialchars($commentaire) : '' ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Envoyer l'avis</button>
        <a href="mes_reservations.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>

<?php require_once("../templates/footer.php"); ?>
