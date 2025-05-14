<?php
require_once("config/database.php");
require_once("templates/header.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php?message=Connectez-vous pour accéder à vos réservations.");
    exit();
}

$user_id = $_SESSION['user_id'];

// Requête pour récupérer les trajets réservés
$sql = "SELECT r.id AS reservation_id, r.date_reservation, 
               t.*, v.marque, v.modele, v.energie
        FROM reservations r
        JOIN trajets t ON r.trajet_id = t.id
        LEFT JOIN vehicules v ON t.vehicule_id = v.id
        WHERE r.user_id = :user_id
        ORDER BY r.date_reservation DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
     <h2>🚗 Mes réservations</h2>

<?php if (isset($_GET['signalement']) && $_GET['signalement'] === 'envoye'): ?>
    <div class="alert alert-warning">🚨 Votre signalement a bien été transmis. Merci pour votre retour.</div>
<?php endif; ?>

<?php if (isset($_GET['confirmation']) && $_GET['confirmation'] === 'ok'): ?>
    <div class="alert alert-success">✅ Merci ! Vous avez confirmé que le trajet s’est bien passé.</div>
<?php endif; ?>

    <?php if (count($reservations) === 0): ?>
        <p class="text-muted">Vous n'avez réservé aucun trajet.</p>
    <?php else: ?>
        <?php foreach ($reservations as $res): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($res['depart']) ?> → <?= htmlspecialchars($res['destination']) ?></h5>
                    <p><strong>Date du trajet :</strong> <?= htmlspecialchars($res['date']) ?></p>
                    <p><strong>Réservé le :</strong> <?= date('d/m/Y à H:i', strtotime($res['date_reservation'])) ?></p>
                    <p><strong>Chauffeur :</strong> <?= htmlspecialchars($res['chauffeur']) ?></p>
                    <p><strong>Véhicule :</strong> <?= htmlspecialchars($res['marque'] . ' ' . $res['modele']) ?> (<?= htmlspecialchars($res['energie']) ?>)</p>

                    <?php
                    $dateTrajet = new DateTime($res['date']);
                    $dateAuj = new DateTime();

                    // Vérifie si un avis/confirmation a déjà été laissé
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM confirmations WHERE id_trajet = ? AND id_passager = ?");
                    $stmt->execute([$res['id'], $user_id]);
                    $deja_note = $stmt->fetchColumn() > 0;

                    // Si le trajet est terminé
                    if ($dateTrajet < $dateAuj):
                        if ($deja_note):
                            echo '<span class="text-success">✔️ Trajet déjà évalué</span>';
                        else:
                            echo '<a href="confirmer_covoiturage.php?id=' . $res['id'] . '" class="btn btn-primary">📝 Déposer un avis</a>';

                            echo '<a href="signaler_trajet.php?id=' . $res['id'] . '" class="btn btn-danger">❌ Signaler un problème</a>';
                        endif;
                    else:
                        // Sinon, afficher le bouton d’annulation
                    ?>
                        <form action="annuler_reservations.php" method="POST" onsubmit="return confirm('Confirmer l\'annulation ?');" class="d-inline-block">
                            <input type="hidden" name="reservation_id" value="<?= $res['reservation_id'] ?>">
                            <button type="submit" class="btn btn-danger">❌ Annuler</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once("templates/footer.php"); ?>
