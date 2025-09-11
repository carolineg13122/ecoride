<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// --- Accès employé uniquement
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employe') {
    header("Location: /controllers/connexion.php");
    exit();
}

// CSRF token pour les actions
if (empty($_SESSION['csrf_signalements'])) {
    $_SESSION['csrf_signalements'] = bin2hex(random_bytes(32));
}

// Récupérer les signalements "probleme" non validés
$sql = "
    SELECT 
        c.id,
        c.id_trajet,
        c.commentaire,
        c.statut,
        c.valide,
        u.nom  AS nom_passager,
        u.prenom AS prenom_passager,
        t.depart,
        t.destination,
        t.date,
        t.prix,
        t.user_id AS chauffeur_id
    FROM confirmations c
    JOIN users u ON c.id_passager = u.id
    JOIN trajets t ON c.id_trajet = t.id
    WHERE c.statut = 'probleme' AND c.valide = 0
    ORDER BY t.date DESC
";
$stmt = $conn->query($sql);
$signalements = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container mt-5">
    <h2>🛑 Signalements de trajets</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (empty($signalements)): ?>
        <p class="text-muted mt-3">Aucun signalement en attente.</p>
    <?php else: ?>
        <?php foreach ($signalements as $s): 
            $dateTrajet = !empty($s['date']) ? date('d/m/Y', strtotime($s['date'])) : '—';
        ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-2">
                        Trajet : 
                        <strong><?= htmlspecialchars($s['depart'], ENT_QUOTES, 'UTF-8') ?></strong> → 
                        <strong><?= htmlspecialchars($s['destination'], ENT_QUOTES, 'UTF-8') ?></strong>
                        (<?= $dateTrajet ?>)
                    </h5>

                    <p class="mb-1">
                        <strong>Passager :</strong>
                        <?= htmlspecialchars($s['prenom_passager'] . ' ' . $s['nom_passager'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="mb-1"><strong>Prix :</strong> <?= htmlspecialchars((string)$s['prix'], ENT_QUOTES, 'UTF-8') ?> crédits</p>

                    <div class="mt-3">
                        <strong>Commentaire :</strong><br>
                        <?= nl2br(htmlspecialchars($s['commentaire'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                        <!-- ✅ Créditer quand même -->
                        <form action="/controllers/traiter_signalement.php" method="POST" class="me-md-2">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_signalements'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="trajet_id" value="<?= (int)$s['id_trajet'] ?>">
                            <input type="hidden" name="chauffeur_id" value="<?= (int)$s['chauffeur_id'] ?>">
                            <input type="hidden" name="confirmation_id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="prix" value="<?= htmlspecialchars((string)$s['prix'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="crediter">
                            <button type="submit" class="btn btn-success">✅ Créditer quand même</button>  
                        </form>

                        <!-- 🚫 Ne pas créditer -->
                        <form action="/controllers/traiter_signalement.php" method="POST" onsubmit="return confirm('Confirmer que le chauffeur ne sera pas crédité ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_signalements'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="confirmation_id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="action" value="refuser">
                            <button type="submit" class="btn btn-outline-danger">🚫 Ne pas créditer</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
