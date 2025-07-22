<?php
require_once("../config/database.php");
require_once("../templates/header.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
   header("Location: ../controllers/connexion.php?message=Veuillez vous connecter pour modifier un trajet.");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_GET['id'] ?? null;

if (!$trajet_id) {
    die("Trajet non spécifié.");
}

$sql = "SELECT * FROM trajets WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$trajet_id, $user_id]);
$trajet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    die("Trajet introuvable ou vous n'avez pas l'autorisation.");
}
?>

<div class="container mt-5">
    <h2 class="mb-4">✏️ Modifier le trajet</h2>
    <form action="traiter_modification_trajet.php" method="POST" class="row g-4">
        <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet['id']) ?>">

        <div class="col-md-6">
            <label for="depart" class="form-label">Ville de départ</label>
            <input type="text" class="form-control" name="depart" id="depart" value="<?= htmlspecialchars($trajet['depart']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="adresse_depart" class="form-label">Adresse de départ</label>
            <input type="text" class="form-control" name="adresse_depart" id="adresse_depart" value="<?= htmlspecialchars($trajet['adresse_depart']) ?>" required>
        </div>

        <div class="col-md-6">
            <label for="destination" class="form-label">Ville d'arrivée</label>
            <input type="text" class="form-control" name="destination" id="destination" value="<?= htmlspecialchars($trajet['destination']) ?>" required>
        </div>
        <div class="col-md-6">
            <label for="adresse_arrivee" class="form-label">Adresse d'arrivée</label>
            <input type="text" class="form-control" name="adresse_arrivee" id="adresse_arrivee" value="<?= htmlspecialchars($trajet['adresse_arrivee']) ?>" required>
        </div>

        <div class="col-md-4">
            <label for="date" class="form-label">Date</label>
            <input type="date" class="form-control" name="date" id="date" value="<?= htmlspecialchars($trajet['date']) ?>" required>
        </div>
        <div class="col-md-4">
            <label for="prix" class="form-label">Prix (€)</label>
            <input type="number" class="form-control" name="prix" id="prix" value="<?= htmlspecialchars($trajet['prix']) ?>" required>
        </div>
        <div class="col-md-4">
            <label for="places" class="form-label">Places disponibles</label>
            <input type="number" class="form-control" name="places" id="places" value="<?= htmlspecialchars($trajet['places']) ?>" required>
        </div>
        <div class="col-md-4">
            <label for="duree_minutes" class="form-label">Durée estimée (en minutes)</label>
            <input type="number" class="form-control" name="duree_minutes" id="duree_minutes"
                value="<?= htmlspecialchars($trajet['duree_minutes']) ?>" required min="1">
        </div>

        <div class="col-md-12">
            <label for="preferences" class="form-label">Préférences du chauffeur</label>
            <input type="text" class="form-control" name="preferences" id="preferences" value="<?= htmlspecialchars($trajet['preferences']) ?>">
        </div>

        <div class="col-md-6">
            <label for="fumeur" class="form-label">Autoriser les fumeurs</label>
            <select class="form-select" name="fumeur" id="fumeur">
                <option value="1" <?= $trajet['fumeur'] ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= !$trajet['fumeur'] ? 'selected' : '' ?>>Non</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="eco" class="form-label">Voyage écologique</label>
            <select class="form-select" name="eco" id="eco">
                <option value="1" <?= $trajet['eco'] ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= !$trajet['eco'] ? 'selected' : '' ?>>Non</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-between">
            <a href="mes_trajets.php" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-success">💾 Enregistrer les modifications</button>
        </div>
    </form>
</div>

<?php require_once("../templates/footer.php"); ?>
