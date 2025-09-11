<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth : employé uniquement
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employe') {
    header("Location: /controllers/connexion.php");
    exit();
}

// Token CSRF
if (empty($_SESSION['csrf_avis'])) {
    $_SESSION['csrf_avis'] = bin2hex(random_bytes(32));
}

// Récupère les avis non validés
$sql = "
    SELECT 
        a.id, a.note, a.commentaire, a.date_creation, 
        u.prenom AS passager_prenom, u.nom AS passager_nom,
        t.depart, t.destination, t.date
    FROM avis a
    JOIN users u ON a.utilisateur_id = u.id
    JOIN trajets t ON a.trajet_id = t.id
    WHERE statut = 'en_attente'
    ORDER BY a.date_creation DESC
";
$stmt = $conn->query($sql);
$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-5">
    <h2>🗣 Avis à valider</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <?php if (empty($avis)): ?>
        <p class="text-muted">Aucun avis en attente.</p>
    <?php else: ?>
        <?php foreach ($avis as $a): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>
                        <?= htmlspecialchars($a['passager_prenom'] . ' ' . $a['passager_nom']) ?>
                        ⭐ <?= (int)$a['note'] ?>/5
                    </h5>
                    <p><strong>Trajet :</strong> <?= htmlspecialchars($a['depart']) ?> → <?= htmlspecialchars($a['destination']) ?> (<?= htmlspecialchars(date('d/m/Y', strtotime($a['date']))) ?>)</p>
                    <p><strong>Commentaire :</strong><br><?= nl2br(htmlspecialchars($a['commentaire'])) ?></p>

                    <form method="POST" action="/controllers/traiter_avis.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_avis'] ?>">
                        <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                        <input type="hidden" name="action" value="valider">
                        <button class="btn btn-success btn-sm">✅ Valider</button>
                    </form>

                    <form method="POST" action="/controllers/traiter_avis.php" class="d-inline" onsubmit="return confirm('Confirmer le rejet ?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_avis'] ?>">
                        <input type="hidden" name="avis_id" value="<?= (int)$a['id'] ?>">
                        <input type="hidden" name="action" value="rejeter">
                        <button class="btn btn-danger btn-sm">🚫 Rejeter</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
