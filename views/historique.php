<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../controllers/connexion.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* Trajets en tant que chauffeur */
$sql_chauffeur = "SELECT id, depart, destination, date, places, prix
                  FROM trajets
                  WHERE user_id = ?
                  ORDER BY date DESC";
$stmt_chauffeur = $conn->prepare($sql_chauffeur);
$stmt_chauffeur->execute([$user_id]);
$trajets_chauffeur = $stmt_chauffeur->fetchAll(PDO::FETCH_ASSOC);

/* Trajets en tant que passager */
$sql_passager = "SELECT t.id, t.depart, t.destination, t.date, t.places, t.prix,
                        r.id AS reservation_id
                 FROM trajets t
                 JOIN reservations r ON r.trajet_id = t.id
                 WHERE r.user_id = ?
                 ORDER BY t.date DESC";
$stmt_passager = $conn->prepare($sql_passager);
$stmt_passager->execute([$user_id]);
$trajets_passager = $stmt_passager->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
?>

<div class="container mt-4">

    <?php if (!empty($_GET['message'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <h2>🚗 Mes trajets en tant que chauffeur</h2>

    <?php if (empty($trajets_chauffeur)): ?>
        <p class="text-muted">Aucun trajet proposé pour le moment.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Départ</th>
                <th>Arrivée</th>
                <th>Date</th>
                <th>Places</th>
                <th>Prix</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($trajets_chauffeur as $trajet): ?>
                <tr>
                    <td><?= htmlspecialchars($trajet['depart']) ?></td>
                    <td><?= htmlspecialchars($trajet['destination']) ?></td>
                    <td><?= htmlspecialchars($trajet['date']) ?></td>
                    <td><?= (int)$trajet['places'] ?></td>
                    <td><?= htmlspecialchars($trajet['prix']) ?> crédits</td>
                    <td>
                        <!-- Chez toi, l’annulation chauffeur est gérée par supprimer_trajet.php (GET) -->
                        <a href="supprimer_trajet.php?id=<?= (int)$trajet['id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Annuler ce trajet ? Les passagers seront remboursés et prévenus.');">
                            Annuler
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 class="mt-5">🧍‍♂️ Mes trajets en tant que passager</h2>

    <?php if (empty($trajets_passager)): ?>
        <p class="text-muted">Aucune réservation pour le moment.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Départ</th>
                <th>Arrivée</th>
                <th>Date</th>
                <th>Places</th>
                <th>Prix</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($trajets_passager as $trajet): ?>
                <tr>
                    <td><?= htmlspecialchars($trajet['depart']) ?></td>
                    <td><?= htmlspecialchars($trajet['destination']) ?></td>
                    <td><?= htmlspecialchars($trajet['date']) ?></td>
                    <td><?= (int)$trajet['places'] ?></td>
                    <td><?= htmlspecialchars($trajet['prix']) ?> crédits</td>
                    <td>
                        <!-- IMPORTANT: ton fichier d’annulation passager attend un POST !
                             On envoie donc un formulaire POST vers annuler_reservations.php -->
                        <form action="annuler_reservations.php" method="POST"
                              onsubmit="return confirm('Annuler cette réservation ? Vos crédits seront remboursés.');"
                              class="d-inline">
                            <input type="hidden" name="reservation_id" value="<?= (int)$trajet['reservation_id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm">Annuler</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php require_once '../templates/footer.php'; ?>
