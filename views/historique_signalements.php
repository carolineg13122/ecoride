<?php
session_start();
require_once '../config/database.php';

// Vérification admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../controllers/connexion.php");
    exit();
}

// Requête : récupérer tous les signalements traités
$stmt = $conn->prepare("
    SELECT 
        c.*, 
        u.nom AS nom_passager, u.prenom AS prenom_passager,
        t.depart, t.destination, t.date,
        e.nom AS nom_employe, e.prenom AS prenom_employe
    FROM confirmations c
    JOIN users u ON c.id_passager = u.id
    JOIN trajets t ON c.id_trajet = t.id
    LEFT JOIN users e ON c.traite_par = e.id
    WHERE c.valide = 1
      AND c.statut IN ('ok','rejete') 
    ORDER BY c.date_validation DESC
");
$stmt->execute();
$signalements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../templates/header.php'; ?>

<div class="container mt-5">
    <h2>📜 Historique des signalements traités</h2>

    <?php if (count($signalements) === 0): ?>
        <p class="text-muted">Aucun signalement traité pour le moment.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Trajet</th>
                    <th>Passager</th>
                    <th>Commentaire</th>
                    <th>Traité par</th>
                    <th>Date</th>
                    <th>Décision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signalements as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['depart']) ?> → <?= htmlspecialchars($s['destination']) ?> (<?= htmlspecialchars($s['date']) ?>)</td>
                        <td><?= htmlspecialchars($s['prenom_passager']) ?> <?= htmlspecialchars($s['nom_passager']) ?></td>
                        <td><?= nl2br(htmlspecialchars($s['commentaire'])) ?></td>
                        <td>
                            <?= $s['nom_employe'] && $s['prenom_employe']
                                ? htmlspecialchars($s['prenom_employe'] . ' ' . $s['nom_employe'])
                                : 'Non identifié' ?>
                        </td>
                        <td>
                            <?= !empty($s['date_validation']) 
                                ? date('d/m/Y H:i', strtotime($s['date_validation']))
                                : '-' ?>
                        </td>
                        <td>
                            <?php
                            if ($s['statut'] === 'ok') {
                                echo '<span class="text-success fw-bold">✅ Crédité</span>';
                            } elseif ($s['statut'] === 'rejete') {
                                echo '<span class="text-danger fw-bold">🚫 Non crédité</span>';
                            } else {
                                echo '<span class="text-muted">Inconnu</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../templates/footer.php'; ?>
