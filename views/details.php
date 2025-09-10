<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../templates/header.php';

// --- ID du trajet ---
$trajet_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$trajet_id) {
    echo '<div class="container mt-5 alert alert-danger">Aucun trajet spécifié.</div>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// --- Trajet + véhicule + chauffeur ---
$sqlTrajet = "
    SELECT t.*, v.marque, v.modele, v.energie,
           u.prenom, u.nom, u.photo
    FROM trajets t
    LEFT JOIN vehicules v ON t.vehicule_id = v.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = :id
";
$stmtTrajet = $conn->prepare($sqlTrajet);
$stmtTrajet->execute([':id' => $trajet_id]);
$trajet = $stmtTrajet->fetch(PDO::FETCH_ASSOC);

if (!$trajet) {
    echo '<div class="container mt-5 alert alert-warning">Trajet introuvable.</div>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// --- Avis validés ---
$sqlAvis = "
    SELECT a.*, u.nom, u.prenom
    FROM avis a
    JOIN users u ON a.utilisateur_id = u.id
    WHERE a.trajet_id = :trajet_id AND a.valide = 1
    ORDER BY a.created_at DESC
";
$stmtAvis = $conn->prepare($sqlAvis);
$stmtAvis->execute([':trajet_id' => $trajet_id]);
$avis = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

// --- Moyenne des notes ---
$moyenne = null;
if ($avis) {
    $notes = array_column($avis, 'note');
    if ($notes) {
        $moyenne = round(array_sum($notes) / count($notes), 1);
    }
}

// --- Variables protégées ---
$chauffeur = trim(($trajet['prenom'] ?? '') . ' ' . ($trajet['nom'] ?? ''));
$depart = $trajet['depart'] ?? 'Non précisé';
$destination = $trajet['destination'] ?? 'Non précisé';
$date = $trajet['date'] ?? 'Non précisée';
$prix = $trajet['prix'] ?? null;
$places = (int)($trajet['places'] ?? 0);
$eco = (int)($trajet['eco'] ?? 0);
$marque = $trajet['marque'] ?? 'Non précisée';
$modele = $trajet['modele'] ?? 'Non précisé';
$energie = $trajet['energie'] ?? 'Non précisée';
$preferences = trim((string)($trajet['preferences'] ?? ''));
$preferencesList = $preferences !== '' ? array_map('trim', explode(',', $preferences)) : [];
$photo = $trajet['photo'] ?? null;

// Détection du mime de la photo si dispo
$imgSrc = null;
if (!empty($photo)) {
    $mime = 'image/jpeg';
    if (function_exists('finfo_open')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $det = $finfo->buffer($photo);
        if ($det) { $mime = $det; }
    }
    $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($photo);
}
?>
<div class="container mt-5">
    <div class="row">
        <!-- Photo + chauffeur -->
        <div class="col-md-4 text-center">
            <?php if ($imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
                     alt="Photo de <?= htmlspecialchars($chauffeur, ENT_QUOTES, 'UTF-8') ?>"
                     class="rounded-circle img-fluid mb-3" width="200">
            <?php else: ?>
                <img src="../assets/images/OIP.jpg"
                     alt="Photo par défaut"
                     class="rounded-circle img-fluid mb-3" width="200">
            <?php endif; ?>

            <h3><?= htmlspecialchars($chauffeur, ENT_QUOTES, 'UTF-8') ?></h3>
            <p><strong>Note :</strong> <?= $moyenne !== null ? htmlspecialchars((string)$moyenne, ENT_QUOTES, 'UTF-8') . ' / 5' : 'Non noté' ?></p>
        </div>

        <!-- Infos trajet -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title text-center">🚗 Détails du trajet</h4>
                    <p><strong>Départ :</strong> <?= htmlspecialchars($depart, ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Arrivée :</strong> <?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Date :</strong> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Prix :</strong>
                        <?php if ($prix !== null): ?>
                            <span class="badge badge-primary"><?= htmlspecialchars(number_format((float)$prix, 2, ',', ' '), ENT_QUOTES, 'UTF-8') ?> €</span>
                        <?php else: ?>
                            Non précisé
                        <?php endif; ?>
                    </p>
                    <p><strong>Places restantes :</strong> <?= htmlspecialchars((string)$places, ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Voyage écologique :</strong>
                        <?= $eco === 1
                            ? "<span class='badge badge-success'>Oui</span>"
                            : "<span class='badge badge-secondary'>Non</span>"; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <!-- Avis -->
    <h4 class="mt-5">Avis des passagers</h4>
    <?php if (!$avis): ?>
        <p>Aucun avis pour ce trajet.</p>
    <?php else: ?>
        <?php foreach ($avis as $a): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <strong><?= htmlspecialchars(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <p>Note : <?= htmlspecialchars((string)$a['note'], ENT_QUOTES, 'UTF-8') ?>/5</p>
                    <p><?= nl2br(htmlspecialchars((string)($a['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php if (!empty($a['created_at'])): ?>
                        <small>Posté le <?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <hr>

    <!-- Véhicule -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h4 class="card-title text-center">🚙 Véhicule</h4>
            <p><strong>Marque :</strong> <?= htmlspecialchars($marque, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Modèle :</strong> <?= htmlspecialchars($modele, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Énergie :</strong> <?= htmlspecialchars($energie, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <hr>

    <!-- Préférences -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <h4 class="card-title text-center">✅ Préférences du chauffeur</h4>
            <?php if ($preferencesList): ?>
                <ul class="list-group">
                    <?php foreach ($preferencesList as $pref): ?>
                        <?php if ($pref !== ''): ?>
                            <li class="list-group-item">
                                <span class="badge badge-info"><?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Aucune préférence spécifiée.</p>
            <?php endif; ?>
        </div>
    </div>

    <hr>

    <!-- Réserver -->
    <div class="text-center mt-4">
        <?php if ($places > 0): ?>
            <form action="../views/reserver.php" method="POST">
                <input type="hidden" name="trajet_id" value="<?= htmlspecialchars((string)$trajet_id, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="depart" value="<?= htmlspecialchars($depart, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="destination" value="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="prix" value="<?= htmlspecialchars((string)($prix ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-success">🚗 Participer au covoiturage</button>
            </form>
        <?php else: ?>
            <p class="text-danger"><strong>❌ Plus de places disponibles pour ce trajet.</strong></p>
        <?php endif; ?>
    </div>

    <div class="text-center mt-4">
        <a href="../index.php" class="btn btn-secondary">Retour</a>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
