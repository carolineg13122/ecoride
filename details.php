<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("templates/header.php");
require_once("config/database.php");

// Récupération de l'ID du trajet depuis l'URL
$trajet_id = $_GET['id'] ?? null;
if (!$trajet_id) {
    echo "Aucun trajet spécifié.";
    exit;
}

// 🔍 Requête trajet + véhicule
$sqlTrajet = "SELECT t.*, v.marque, v.modele, v.energie, u.prenom, u.nom, u.photo 
              FROM trajets t
              LEFT JOIN vehicules v ON t.vehicule_id = v.id
              LEFT JOIN users u ON t.user_id = u.id
              WHERE t.id = :id";
$stmtTrajet = $conn->prepare($sqlTrajet);
$stmtTrajet->execute([':id' => $trajet_id]);
$trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    echo "Trajet introuvable.";
    exit;
}

// 🔍 Récupération des avis validés
$sqlAvis = "SELECT a.*, u.nom, u.prenom 
            FROM avis a
            JOIN users u ON a.utilisateur_id = u.id
            WHERE a.trajet_id = :trajet_id AND a.valide = 1
            ORDER BY a.created_at DESC";
$stmtAvis = $conn->prepare($sqlAvis);
$stmtAvis->execute([':trajet_id' => $trajet_id]);
$avis = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

// ✅ Calcul de la moyenne des notes
$moyenne = null;
if (count($avis) > 0) {
    $notes = array_column($avis, 'note');
    $moyenne = round(array_sum($notes) / count($notes), 1);
}

// Variables du trajet
$chauffeur = trim(($trajet['prenom'] ?? '') . ' ' . ($trajet['nom'] ?? ''));
$photo = $trajet['photo'] ?? null;
$depart = $trajet['depart'] ?? 'Non précisé';
$destination = $trajet['destination'] ?? 'Non précisé';
$date = $trajet['date'] ?? 'Non précisée';
$prix = $trajet['prix'] ?? 'Non précisé';
$places = $trajet['places'] ?? 0;
$eco = $trajet['eco'] ?? 0;
$marque = $trajet['marque'] ?? 'Non précisée';
$modele = $trajet['modele'] ?? 'Non précisé';
$energie = $trajet['energie'] ?? 'Non précisée';
$preferences = $trajet['preferences'] ?? '';
$preferencesList = explode(", ", $preferences);
?>

<div class="container mt-5">
    <div class="row">
        <!-- Photo et infos du chauffeur -->
        <div class="col-md-4 text-center">
            <?php if (!empty($photo)): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($photo) ?>" 
                     alt="Photo de <?= htmlspecialchars($chauffeur) ?>" 
                     class="rounded-circle img-fluid mb-3" width="200">
            <?php else: ?>
                <img src="assets/images/OIP.jpg" 
                     alt="Photo par défaut" 
                     class="rounded-circle img-fluid mb-3" width="200">
            <?php endif; ?>

            <h3><?= htmlspecialchars($chauffeur) ?> ⭐</h3>
            <p><strong>Note :</strong> <?= $moyenne !== null ? "$moyenne / 5" : 'Non noté' ?></p>
        </div>

        <!-- Infos du trajet -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title text-center">🚗 Détails du trajet</h4>
                    <p><strong>Départ :</strong> <?= htmlspecialchars($depart) ?></p>
                    <p><strong>Arrivée :</strong> <?= htmlspecialchars($destination) ?></p>
                    <p><strong>Date :</strong> <?= htmlspecialchars($date) ?></p>
                    <p><strong>Prix :</strong> <span class="badge badge-primary"><?= htmlspecialchars($prix) ?>€</span></p>
                    <p><strong>Places restantes :</strong> <?= htmlspecialchars($places) ?></p>
                    <p><strong>Voyage écologique :</strong> 
                        <?= $eco == 1 
                            ? "<span class='badge badge-success'>Oui</span>" 
                            : "<span class='badge badge-secondary'>Non</span>"; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <!-- Section des avis -->
    <h4 class="mt-5">Avis des passagers</h4>
    <?php if (count($avis) === 0): ?>
        <p>Aucun avis pour ce trajet.</p>
    <?php else: ?>
        <?php foreach ($avis as $a): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <strong><?= htmlspecialchars($a['prenom']) ?> <?= htmlspecialchars($a['nom']) ?></strong>
                    <p>Note : <?= htmlspecialchars($a['note']) ?>/5</p>
                    <p><?= nl2br(htmlspecialchars($a['commentaire'])) ?></p>
                    <small>Posté le <?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <hr>

    <!-- Détails sur le véhicule -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h4 class="card-title text-center">🚙 Véhicule</h4>
            <p><strong>Marque :</strong> <?= htmlspecialchars($marque) ?></p>
            <p><strong>Modèle :</strong> <?= htmlspecialchars($modele) ?></p>
            <p><strong>Énergie :</strong> <?= htmlspecialchars($energie) ?></p>
        </div>
    </div>

    <hr>

    <!-- Préférences du chauffeur -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h4 class="card-title text-center">✅ Préférences du chauffeur</h4>
            <ul class="list-group">
                <?php foreach ($preferencesList as $pref): ?>
                    <li class="list-group-item"><span class="badge badge-info"><?= htmlspecialchars($pref) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <hr>

    <!-- Bouton pour participer -->
    <div class="text-center mt-4">
        <?php if ($places > 0): ?>
            <form action="reserver.php" method="POST">
                <input type="hidden" name="chauffeur" value="<?= htmlspecialchars($chauffeur) ?>">
                <input type="hidden" name="depart" value="<?= htmlspecialchars($depart) ?>">
                <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                <input type="hidden" name="prix" value="<?= htmlspecialchars($prix) ?>">
                <input type="hidden" name="places" value="<?= htmlspecialchars($places) ?>">
                <input type="hidden" name="eco" value="<?= htmlspecialchars($eco) ?>">
                <input type="hidden" name="trajet_id" value="<?= htmlspecialchars($trajet_id) ?>">
                <button type="submit" class="btn btn-success">🚗 Participer au covoiturage</button>
            </form>
        <?php else: ?>
            <p class="text-danger"><strong>❌ Plus de places disponibles pour ce trajet.</strong></p>
        <?php endif; ?>
    </div>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-secondary">Retour</a>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
