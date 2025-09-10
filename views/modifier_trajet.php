<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Veuillez vous connecter pour modifier un trajet.'));
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$trajet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trajet_id <= 0) {
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet non spécifié.'));
    exit;
}

// Charger le trajet de l’utilisateur
$sql  = 'SELECT * FROM trajets WHERE id = ? AND user_id = ?';
$stmt = $conn->prepare($sql);
$stmt->execute([$trajet_id, $user_id]);
$trajet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    header('Location: /views/mes_trajets.php?message=' . rawurlencode("Trajet introuvable ou non autorisé."));
    exit;
}

// Si la colonne "date" est DATETIME, il faut fournir YYYY-MM-DD au <input type="date">
$val_date = $trajet['date'] ?? '';
if ($val_date && strlen($val_date) > 10) { // ex: "2025-09-09 14:30:00" -> "2025-09-09"
    $val_date = substr($val_date, 0, 10);
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">✏️ Modifier le trajet</h2>

    <form action="/controllers/traiter_modification_trajet.php" method="POST" class="row g-4">
        <input type="hidden" name="trajet_id" value="<?= (int)$trajet['id'] ?>">

        <div class="col-md-6">
            <label for="depart" class="form-label">Ville de départ</label>
            <input type="text" class="form-control" name="depart" id="depart"
                   value="<?= htmlspecialchars($trajet['depart'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="adresse_depart" class="form-label">Adresse de départ</label>
            <input type="text" class="form-control" name="adresse_depart" id="adresse_depart"
                   value="<?= htmlspecialchars($trajet['adresse_depart'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="col-md-6">
            <label for="destination" class="form-label">Ville d'arrivée</label>
            <input type="text" class="form-control" name="destination" id="destination"
                   value="<?= htmlspecialchars($trajet['destination'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="col-md-6">
            <label for="adresse_arrivee" class="form-label">Adresse d'arrivée</label>
            <input type="text" class="form-control" name="adresse_arrivee" id="adresse_arrivee"
                   value="<?= htmlspecialchars($trajet['adresse_arrivee'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="col-md-4">
            <label for="date" class="form-label">Date</label>
            <input type="date" class="form-control" name="date" id="date"
                   value="<?= htmlspecialchars($val_date, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="col-md-4">
            <label for="prix" class="form-label">Prix (€)</label>
            <input type="number" class="form-control" name="prix" id="prix" min="0" step="0.01"
                   value="<?= htmlspecialchars((string)($trajet['prix'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="col-md-4">
            <label for="places" class="form-label">Places disponibles</label>
            <input type="number" class="form-control" name="places" id="places" min="1"
                   value="<?= (int)($trajet['places'] ?? 1) ?>" required>
        </div>

        <div class="col-md-4">
            <label for="duree_minutes" class="form-label">Durée estimée (en minutes)</label>
            <input type="number" class="form-control" name="duree_minutes" id="duree_minutes" min="1"
                   value="<?= (int)($trajet['duree_minutes'] ?? 1) ?>" required>
        </div>

        <div class="col-md-12">
            <label for="preferences" class="form-label">Préférences du chauffeur</label>
            <input type="text" class="form-control" name="preferences" id="preferences"
                   value="<?= htmlspecialchars($trajet['preferences'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-6">
            <label for="fumeur" class="form-label">Autoriser les fumeurs</label>
            <select class="form-select" name="fumeur" id="fumeur">
                <option value="1" <?= !empty($trajet['fumeur']) ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= empty($trajet['fumeur']) ? 'selected' : '' ?>>Non</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="eco" class="form-label">Voyage écologique</label>
            <select class="form-select" name="eco" id="eco">
                <option value="1" <?= !empty($trajet['eco']) ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= empty($trajet['eco']) ? 'selected' : '' ?>>Non</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-between">
            <a href="/views/mes_trajets.php" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-success">💾 Enregistrer les modifications</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
