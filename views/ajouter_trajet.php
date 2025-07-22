<?php
require_once("../templates/header.php");
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php?message=Veuillez vous connecter pour publier un trajet.");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les véhicules de l'utilisateur
$sql = "SELECT * FROM vehicules WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2 class="mb-4">🚗 Publier un trajet</h2>

    <form action="../controllers/traiter_trajet.php" method="POST">
        <!-- Infos du trajet -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="depart">Ville de départ</label>
                    <input type="text" name="depart" id="depart" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="adresse_depart">Adresse de départ</label>
                    <input type="text" name="adresse_depart" id="adresse_depart" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="destination">Ville d'arrivée</label>
                    <input type="text" name="destination" id="destination" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="adresse_arrivee">Adresse d'arrivée</label>
                    <input type="text" name="adresse_arrivee" id="adresse_arrivee" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="date">Date du trajet</label>
                    <input type="date" name="date" id="date" class="form-control" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="prix">Prix (€)</label>
                    <input type="number" name="prix" id="prix" class="form-control" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="places">Places disponibles</label>
                    <input type="number" name="places" id="places" class="form-control" required min="1">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="duree_minutes">⏱️ Durée estimée (en minutes)</label>
            <input type="number" name="duree_minutes" id="duree_minutes" class="form-control" required min="1" placeholder="ex : 90">
        </div>

        <!-- Sélection du véhicule -->
        <hr>
        <h4 class="mb-3">🚙 Véhicule</h4>

        <?php if (!empty($vehicules)): ?>
            <div class="form-group mb-3">
                <label for="vehicule_id">Choisir un véhicule existant :</label>
                <select name="vehicule_id" id="vehicule_id" class="form-control">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($vehicules as $vehicule): ?>
                        <option value="<?= $vehicule['id'] ?>" 
                            data-plaque="<?= htmlspecialchars($vehicule['plaque_immatriculation']) ?>"
                            data-date="<?= htmlspecialchars($vehicule['date_immatriculation']) ?>">
                            <?= htmlspecialchars($vehicule['marque']) ?> <?= htmlspecialchars($vehicule['modele']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="plaque_immatriculation">Plaque d'immatriculation</label>
                <input type="text" id="plaque_immatriculation" name="plaque_immatriculation" class="form-control" readonly>
            </div>

            <div class="form-group mb-3">
                <label for="date_immatriculation">Date d'immatriculation</label>
                <input type="date" id="date_immatriculation" name="date_immatriculation" class="form-control" readonly>
            </div>
        <?php endif; ?>

        <div class="form-group mb-3">
            <label>Ou ajouter un nouveau véhicule :</label>
            <input type="text" name="marque" class="form-control mb-2" placeholder="Marque">
            <input type="text" name="modele" class="form-control mb-2" placeholder="Modèle">
            <select name="energie" class="form-control mb-2">
                <option value="">-- Type d'énergie --</option>
                <option value="Électrique">Électrique</option>
                <option value="Essence">Essence</option>
                <option value="Diesel">Diesel</option>
                <option value="Hybride">Hybride</option>
            </select>
            <input type="text" name="plaque_immatriculation" class="form-control mb-2" placeholder="Plaque d'immatriculation">
            <input type="date" name="date_immatriculation" class="form-control mb-2">
        </div>

        <!-- Préférences du chauffeur -->
        <hr>
        <h4 class="mb-3">✅ Préférences</h4>

        <div class="form-group mb-3">
            <label for="preferences">Préférences du chauffeur</label>
            <input type="text" name="preferences" id="preferences" class="form-control" placeholder="Ex : Accepte les animaux, Climatisation">
        </div>

        <div class="form-group mb-3">
            <label for="fumeur">Autoriser les fumeurs ?</label>
            <select name="fumeur" id="fumeur" class="form-control">
                <option value="1">Oui</option>
                <option value="0">Non</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="eco">Voyage écologique ?</label>
            <select name="eco" id="eco" class="form-control">
                <option value="1">Oui</option>
                <option value="0">Non</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success mt-3">Publier le trajet</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vehiculeSelect = document.getElementById('vehicule_id');
    if (vehiculeSelect) {
        vehiculeSelect.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            var plaque = selected.getAttribute('data-plaque');
            var date = selected.getAttribute('data-date');

            document.getElementById('plaque_immatriculation').value = plaque || '';
            document.getElementById('date_immatriculation').value = date || '';
        });
    }
});
</script>

<?php require_once("../templates/footer.php"); ?>