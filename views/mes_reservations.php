<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth obligatoire
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Connectez-vous pour accéder à vos réservations.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* --- Récupération des réservations de l'utilisateur --- */
$sql = "
    SELECT 
        r.id AS reservation_id, 
        r.date_reservation, 
        t.*, 
        v.marque, v.modele, v.energie
    FROM reservations r
    JOIN trajets t ON r.trajet_id = t.id
    LEFT JOIN vehicules v ON t.vehicule_id = v.id
    WHERE r.user_id = :user_id
    ORDER BY r.date_reservation DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-5">
    <h2>🚗 Mes réservations</h2>

    <?php if (isset($_GET['signalement']) && $_GET['signalement'] === 'envoye'): ?>
        <div class="alert alert-warning">🚨 Votre signalement a bien été transmis. Merci pour votre retour.</div>
    <?php endif; ?>

    <?php if (isset($_GET['confirmation']) && $_GET['confirmation'] === 'ok'): ?>
        <div class="alert alert-success">✅ Merci ! Vous avez confirmé que le trajet s’est bien passé.</div>
    <?php endif; ?>

    <?php if (empty($reservations)): ?>
        <p class="text-muted">Vous n'avez réservé aucun trajet.</p>
    <?php else: ?>
        <?php foreach ($reservations as $res): ?>
            <?php
            $trajetId = (int)($res['id'] ?? 0); // id du trajet
            $dateTrajet = isset($res['date']) ? new DateTime($res['date']) : null;
            $dateResa   = isset($res['date_reservation']) ? new DateTime($res['date_reservation']) : null;

            // Trajet terminé ?
            $trajet_termine = ($res['statut'] ?? '') === 'termine';

            // Déjà évalué ?
            $stmtC = $conn->prepare('SELECT COUNT(*) FROM avis WHERE utilisateur_id = ? AND trajet_id = ?');
            $stmtC->execute([$user_id, $trajetId]);
            $deja_note = (int)$stmtC->fetchColumn() > 0;

            $maintenant = new DateTime();
            ?>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <?= htmlspecialchars($res['depart'] ?? '', ENT_QUOTES, 'UTF-8') ?> →
                        <?= htmlspecialchars($res['destination'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </h5>

                    <p><strong>Date du trajet :</strong> <?= $dateTrajet ? $dateTrajet->format('d/m/Y') : '—' ?></p>
                    <p><strong>Réservé le :</strong> <?= $dateResa ? $dateResa->format('d/m/Y à H:i') : '—' ?></p>
                    <p><strong>Chauffeur :</strong> <?= htmlspecialchars($res['chauffeur'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Véhicule :</strong> <?= htmlspecialchars(trim(($res['marque'] ?? '') . ' ' . ($res['modele'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($res['energie'])): ?>
                            (<?= htmlspecialchars($res['energie'], ENT_QUOTES, 'UTF-8') ?>)
                        <?php endif; ?>
                    </p>

                    <?php if ($trajet_termine): ?>
                        <?php if ($deja_note): ?>
                            <span class="text-success">✔️ Trajet déjà évalué</span>
                        <?php else: ?>
                            <a href="/views/laisser_avis.php?id=<?= $trajetId ?>" class="btn btn-success">📝 Déposer un avis</a>
                            <a href="/views/signaler_trajet.php?id=<?= $trajetId ?>" class="btn btn-danger ms-2">❌ Signaler un problème</a>
                        <?php endif; ?>
                    <?php elseif ($dateTrajet && $dateTrajet > $maintenant): ?>
                        <!-- Trajet à venir : permettre l’annulation -->
                        <form action="/controllers/annuler_reservations.php" method="POST"
                              onsubmit="return confirm('Confirmer l\'annulation de cette réservation ?');"
                              class="d-inline-block">
                            <input type="hidden" name="reservation_id" value="<?= (int)($res['reservation_id'] ?? 0) ?>">
                            <button type="submit" class="btn btn-outline-danger">❌ Annuler</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">⏳ En attente de validation du trajet</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
