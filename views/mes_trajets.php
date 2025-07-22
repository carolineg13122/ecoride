<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success mt-3">
        <?= htmlspecialchars($_GET['message']) ?>
    </div>
<?php endif; ?>

<?php 
require_once("../config/database.php");
require_once("../templates/header.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT t.*, v.marque, v.modele FROM trajets t
        LEFT JOIN vehicules v ON t.vehicule_id = v.id
        WHERE t.user_id = ? ORDER BY t.date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2 class="mb-4">🚗 Mes Trajets</h2>


    <?php if (count($trajets) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">

            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Départ</th>
                    <th>Adresse départ</th>
                    <th>Destination</th>
                    <th>Adresse arrivée</th>
                    <th>Durée (min)</th>
                    <th>Prix (€)</th>
                    <th>Places</th>
                    <th>Passagers</th>
                    <th>Véhicule</th>
                    <th>Actions</th>
                    <th>Covoiturage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trajets as $trajet): ?>
                    <tr>
                        <td><?= htmlspecialchars($trajet['date']) ?></td>
                        <td><?= htmlspecialchars($trajet['depart']) ?></td>
                        <td><?= htmlspecialchars($trajet['adresse_depart']) ?></td>
                        <td><?= htmlspecialchars($trajet['destination']) ?></td>
                        <td><?= htmlspecialchars($trajet['adresse_arrivee']) ?></td>
                        <td><?= htmlspecialchars($trajet['duree_minutes']) ?></td>
                        <td><?= htmlspecialchars($trajet['prix']) ?></td>
                        <td><?= htmlspecialchars($trajet['places']) ?></td>
                        <td> <?php
                                // Requête pour récupérer les passagers de ce trajet
                                $stmtP = $conn->prepare("
                                    SELECT u.prenom, u.nom 
                                    FROM reservations r
                                    JOIN users u ON r.user_id = u.id
                                    WHERE r.trajet_id = ?
                                ");
                                $stmtP->execute([$trajet['id']]);
                                $passagers = $stmtP->fetchAll();

                                if (count($passagers) > 0): ?>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($passagers as $p): ?>
                                            <li><?= htmlspecialchars($p['prenom']) ?> <?= htmlspecialchars($p['nom']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <em>Aucun</em>
                                <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($trajet['marque']) ?> <?= htmlspecialchars($trajet['modele']) ?></td>
                        <td>
                            <a href="modifier_trajet.php?id=<?= $trajet['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                            <a href="supprimer_trajet.php?id=<?= $trajet['id'] ?>"
   onclick="return confirm('⚠️ Êtes-vous sûr de vouloir annuler ce trajet ? Tous les passagers seront remboursés et prévenus par mail.')"
   class="btn btn-danger btn-sm">
   Annuler
</a>

                        </td>
                        <td>
                            <?php if ($trajet['statut'] === 'à_venir'): ?>
                                <a href="changer_statut.php?id=<?= $trajet['id'] ?>&action=demarrer" class="btn btn-success btn-sm">🚦 Démarrer</a>
                            <?php elseif ($trajet['statut'] === 'en_cours'): ?>
                                <a href="changer_statut.php?id=<?= $trajet['id'] ?>&action=terminer" class="btn btn-primary btn-sm">🏁 Arrivée</a>
                            <?php elseif ($trajet['statut'] === 'termine'): ?>
                                ✅ Terminé
                            <?php endif; ?>
</td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Aucun trajet trouvé.</div>
    <?php endif; ?>
</div>

<?php require_once("../templates/footer.php"); ?>
