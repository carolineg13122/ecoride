<?php
session_start();
require_once '../config/database.php'; // adapter le chemin selon ton projet

if (!isset($_SESSION['user_id'])) {
    header('Location: ../controllers/connexion.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Récupérer les trajets en tant que CHAUFFEUR
$sql_chauffeur = "SELECT * FROM trajets WHERE user_id = ? ORDER BY date DESC";
$stmt_chauffeur = $conn->prepare($sql_chauffeur);
$stmt_chauffeur->execute([$user_id]);
$trajets_chauffeur = $stmt_chauffeur->fetchAll();

// 2. Récupérer les trajets en tant que PASSAGER
$sql_passager = "SELECT t.*, r.id AS reservation_id FROM trajets t
                 JOIN reservations r ON r.trajet_id = t.id
                 WHERE r.user_id = ?
                 ORDER BY t.date DESC";
$stmt_passager = $conn->prepare($sql_passager);
$stmt_passager->execute([$user_id]);
$trajets_passager = $stmt_passager->fetchAll();
?>
<?php require_once("../templates/header.php"); ?>

<div class="container mt-4">

    <h2>🚗 Mes trajets en tant que chauffeur</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Départ</th><th>Arrivée</th><th>Date</th><th>Places</th><th>Prix</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($trajets_chauffeur as $trajet): ?>
            <tr>
                <td><?= htmlspecialchars($trajet['depart']) ?></td>
                <td><?= htmlspecialchars($trajet['destination']) ?></td>
                <td><?= htmlspecialchars($trajet['date']) ?></td>
                <td><?= htmlspecialchars($trajet['places']) ?></td>
                <td><?= htmlspecialchars($trajet['prix']) ?> crédits</td>
                <td>
                    <a href="supprimer_trajet.php?id=<?= $trajet['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Annuler ce trajet ? Cela enverra un mail aux passagers.');">Annuler</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-5">🧍‍♂️ Mes trajets en tant que passager</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Départ</th><th>Arrivée</th><th>Date</th><th>Places</th><th>Prix</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($trajets_passager as $trajet): ?>
            <tr>
                <td><?= htmlspecialchars($trajet['depart']) ?></td>
                <td><?= htmlspecialchars($trajet['destination']) ?></td>
                <td><?= htmlspecialchars($trajet['date']) ?></td>
                <td><?= htmlspecialchars($trajet['places']) ?></td>
                <td><?= htmlspecialchars($trajet['prix']) ?> crédits</td>
                <td>
                    <a href="supprimer_mes_reservation.php?id=<?= $trajet['reservation_id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('Annuler cette réservation ? Vos crédits seront remboursés.');">Annuler</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
<?php require_once("../templates/footer.php"); ?>
