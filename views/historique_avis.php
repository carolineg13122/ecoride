<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Vérification rôle admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /controllers/connexion.php");
    exit();
}

// Récupération des avis validés
$sql = "
    SELECT 
        a.note, a.commentaire, a.created_at, 
        u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur,
        t.depart, t.destination, t.date AS date_trajet
    FROM avis a
    JOIN users u ON a.utilisateur_id = u.id
    JOIN trajets t ON a.trajet_id = t.id
    WHERE a.valide = 1
    ORDER BY a.created_at DESC
";
$stmt = $conn->query($sql);
$avis_valides = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container mt-5">
    <h2>🗣 Historique des avis validés</h2>

    <?php if (empty($avis_valides)): ?>
        <p class="text-muted">Aucun avis validé à ce jour.</p>
    <?php else: ?>
        <?php foreach ($avis_valides as $avis): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-1">
                        Trajet : <?= htmlspecialchars($avis['depart']) ?> → <?= htmlspecialchars($avis['destination']) ?>
                        (<?= date('d/m/Y', strtotime($avis['date_trajet'])) ?>)
                    </h5>
                    <p class="mb-1"><strong>Passager :</strong> <?= htmlspecialchars($avis['prenom_utilisateur']) ?> <?= htmlspecialchars($avis['nom_utilisateur']) ?></p>
                    <p class="mb-1"><strong>Note :</strong> <?= (float)$avis['note'] ?> / 5</p>
                    <p class="mb-1"><strong>Commentaire :</strong><br>
                        <?= nl2br(htmlspecialchars($avis['commentaire'])) ?>
                    </p>
                    <p class="text-muted"><em>Posté le <?= date('d/m/Y à H:i', strtotime($avis['created_at'])) ?></em></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
