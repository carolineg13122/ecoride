<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth obligatoire
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Récupère les trajets de l'utilisateur + véhicule
$sql = 'SELECT t.*, v.marque, v.modele
        FROM trajets t
        LEFT JOIN vehicules v ON t.vehicule_id = v.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC';
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-5">
    <?php if (!empty($_GET['message'])): ?>
        <div class="alert alert-success mt-3">
            <?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <h2 class="mb-4">🚗 Mes Trajets</h2>

    <?php if (!empty($trajets)): ?>
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
                        <td><?= htmlspecialchars($trajet['date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($trajet['depart'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($trajet['adresse_depart'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($trajet['destination'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($trajet['adresse_arrivee'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($trajet['duree_minutes'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($trajet['prix'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($trajet['places'] ?? 0) ?></td>
                        <td>
                            <?php
                            // Passagers de ce trajet
                            $stmtP = $conn->prepare('
                                SELECT u.prenom, u.nom
                                FROM reservations r
                                JOIN users u ON r.user_id = u.id
                                WHERE r.trajet_id = ?
                            ');
                            $stmtP->execute([(int)$trajet['id']]);
                            $passagers = $stmtP->fetchAll(PDO::FETCH_ASSOC);

                            if (!empty($passagers)): ?>
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($passagers as $p): ?>
                                        <li><?= htmlspecialchars(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <em>Aucun</em>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars(($trajet['marque'] ?? '') . ' ' . ($trajet['modele'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/views/modifier_trajet.php?id=<?= (int)$trajet['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                            <a href="/views/supprimer_trajet.php?id=<?= (int)$trajet['id'] ?>"
                               onclick="return confirm('⚠️ Êtes-vous sûr de vouloir annuler ce trajet ? Tous les passagers seront remboursés et prévenus par mail.')"
                               class="btn btn-danger btn-sm">
                               Annuler
                            </a>
                        </td>
                        <td>
                            <?php if (($trajet['statut'] ?? '') === 'à_venir'): ?>
                                <a href="/controllers/changer_statut.php?id=<?= (int)$trajet['id'] ?>&action=demarrer" class="btn btn-success btn-sm">🚦 Démarrer</a>
                            <?php elseif (($trajet['statut'] ?? '') === 'en_cours'): ?>
                                <a href="/controllers/changer_statut.php?id=<?= (int)$trajet['id'] ?>&action=terminer" class="btn btn-primary btn-sm">🏁 Arrivée</a>
                            <?php elseif (($trajet['statut'] ?? '') === 'termine'): ?>
                                ✅ Terminé
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Aucun trajet trouvé.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
