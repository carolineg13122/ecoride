<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les infos utilisateur (crédits + photo)
$stmt = $conn->prepare("SELECT prenom, credits, photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier les trajets terminés non confirmés
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM trajets t
    JOIN reservations r ON t.id = r.trajet_id
    WHERE r.user_id = ? 
    AND t.statut = 'termine'
    AND t.id NOT IN (
        SELECT id_trajet FROM confirmations WHERE id_passager = ?
    )
");
$stmt->execute([$user_id, $user_id]);
$nb_a_confirmer = $stmt->fetchColumn();

// Préparer la photo
$photo_base64 = null;
$mime_type = null;
if (!empty($user['photo'])) {
    $photo_base64 = base64_encode($user['photo']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->buffer($user['photo']);
}

include 'templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">

            <?php if (isset($_GET['confirmation']) && $_GET['confirmation'] == 'ok'): ?>
                <div class="alert alert-success">✅ Merci ! Vous avez confirmé que le trajet s’est bien passé.</div>
            <?php endif; ?>

            <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'id_manquant' && $_SESSION['role'] === 'utilisateur'): ?>
                <div class="alert alert-danger">❌ ID du trajet manquant.</div>
            <?php endif; ?>


            <h2>👤 Bienvenue <?= htmlspecialchars($user['prenom']) ?> !</h2>
            <div class="d-flex align-items-center gap-4 my-3">
                <?php if (!empty($photo_base64) && !empty($mime_type)): ?>
                    <img src="data:<?= $mime_type ?>;base64,<?= $photo_base64 ?>" class="rounded-circle" width="80">
                <?php else: ?>
                    <img src="./assets/images/OIP.jpg" class="rounded-circle" width="80">
                <?php endif; ?>
                <p class="mb-0">Vous disposez de <strong><?= $user['credits'] ?></strong> crédits.</p>
            </div>

            <?php if ($nb_a_confirmer > 0): ?>
                <div class="alert alert-warning">
                    🚨 Vous avez <strong><?= $nb_a_confirmer ?></strong> trajet(s) terminé(s) à confirmer :
                    <ul class="mt-2">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT t.id AS trajet_id, t.adresse_depart, t.adresse_arrivee
                            FROM trajets t
                            JOIN reservations r ON t.id = r.trajet_id
                            WHERE r.user_id = ?
                            AND t.statut = 'termine'
                            AND t.id NOT IN (
                                SELECT id_trajet FROM confirmations WHERE id_passager = ?
                            )
                        ");
                        $stmt->execute([$user_id, $user_id]);
                        $trajets_a_confirmer = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($trajets_a_confirmer as $t):
                        ?>
                        <li class="mb-2">
                            Trajet de <strong><?= htmlspecialchars($t['adresse_depart']) ?></strong> à <strong><?= htmlspecialchars($t['adresse_arrivee']) ?></strong>
                            <a href="confirmer_covoiturage.php?id=<?= $t['trajet_id'] ?>" class="btn btn-sm btn-success ms-2">✅ Tout s’est bien passé</a>
                            <a href="signaler_trajet.php?id=<?= $t['trajet_id'] ?>" class="btn btn-sm btn-danger ms-2">❌ Signaler un problème</a>

                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Tableau de bord -->
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="card-title">🚗 Mes trajets proposés</h5>
                            <p class="card-text">Gérez vos trajets en tant que chauffeur.</p>
                            <a href="mes_trajets.php" class="btn btn-primary">Voir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body">
                            <h5 class="card-title">🧍‍♂️ Mes réservations</h5>
                            <p class="card-text">Voir les trajets que vous avez réservés.</p>
                            <a href="mes_reservations.php" class="btn btn-success">Voir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body">
                            <h5 class="card-title">📤 Ajouter un trajet</h5>
                            <p class="card-text">Proposez un nouveau covoiturage.</p>
                            <a href="ajouter_trajet.php" class="btn btn-info">Ajouter</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<?php include 'templates/footer.php'; ?>
