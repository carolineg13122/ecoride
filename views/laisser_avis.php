<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Connectez-vous pour laisser un avis.'));
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$trajet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trajet_id <= 0) {
    header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Aucun trajet sélectionné.'));
    exit;
}

// Vérifier que l'utilisateur a bien réservé ce trajet et qu'il est terminé
$stmt = $conn->prepare("
    SELECT t.id, t.statut, t.depart, t.destination, t.date
    FROM reservations r
    JOIN trajets t ON r.trajet_id = t.id
    WHERE r.user_id = ? AND r.trajet_id = ?
    LIMIT 1
");
$stmt->execute([$user_id, $trajet_id]);
$trajet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Réservation introuvable.'));
    exit;
}
if (($trajet['statut'] ?? '') !== 'termine') {
    header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Le trajet doit être terminé pour laisser un avis.'));
    exit;
}

// Anti-doublon : avis déjà laissé ?
$stmt = $conn->prepare('SELECT COUNT(*) FROM avis WHERE utilisateur_id = ? AND trajet_id = ?');
$stmt->execute([$user_id, $trajet_id]);
if ((int)$stmt->fetchColumn() > 0) {
    header('Location: /views/mes_reservations.php?erreur=' . rawurlencode('Vous avez déjà laissé un avis pour ce trajet.'));
    exit;
}

// Traitement formulaire
$erreur = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $note        = isset($_POST['note']) ? (int)$_POST['note'] : 0;
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note < 1 || $note > 5) {
        $erreur = 'La note doit être entre 1 et 5.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO avis (utilisateur_id, trajet_id, note, commentaire, valide, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$user_id, $trajet_id, $note, $commentaire]);

        header('Location: /views/mes_reservations.php?message=' . rawurlencode('Avis envoyé et en attente de validation.'));
        exit;
    }
}

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container mt-5">
    <h2>🗣 Laisser un avis</h2>

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <p class="text-muted">
        Trajet : <strong><?= htmlspecialchars(($trajet['depart'] ?? '') . ' → ' . ($trajet['destination'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        (<?= htmlspecialchars($trajet['date'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
    </p>

    <form method="POST" action="/views/laisser_avis.php?id=<?= (int)$trajet_id ?>">
        <div class="form-group">
            <label for="note">Note (1 à 5) ⭐</label>
            <select name="note" id="note" class="form-control" required>
                <option value="">Choisissez une note</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= (isset($note) && (int)$note === $i) ? 'selected' : '' ?>>
                        <?= $i ?> ⭐
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group mt-3">
            <label for="commentaire">Commentaire</label>
            <textarea name="commentaire" id="commentaire" class="form-control" rows="4" placeholder="Partagez votre expérience..."><?= isset($commentaire) ? htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8') : '' ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Envoyer l'avis</button>
        <a href="/views/mes_reservations.php" class="btn btn-secondary mt-3">Annuler</a>
    </form>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
