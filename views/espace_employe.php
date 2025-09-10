<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

/* --- Accès réservé aux employés --- */
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'employe')) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Accès réservé aux employés.'));
    exit;
}

/* --- Traitement POST (valider / refuser) --- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $avis_id = isset($_POST['avis_id']) ? (int)$_POST['avis_id'] : 0;
    $action  = $_POST['action'] ?? '';

    if ($avis_id > 0 && in_array($action, ['valider','refuser'], true)) {
        if ($action === 'valider') {
            $stmt = $conn->prepare('UPDATE avis SET valide = 1 WHERE id = ?');
            $stmt->execute([$avis_id]);
            $message = 'Avis validé.';
        } else { // refuser
            $stmt = $conn->prepare('DELETE FROM avis WHERE id = ?');
            $stmt->execute([$avis_id]);
            $message = 'Avis supprimé.';
        }
        // Redirection vers la même page (PRG pattern) pour éviter le resoumission F5
        header('Location: /views/espace_employe.php?message=' . rawurlencode($message));
        exit;
    } else {
        header('Location: /views/espace_employe.php?erreur=' . rawurlencode('Requête invalide.'));
        exit;
    }
}

/* --- Récupérer les avis en attente --- */
$stmt = $conn->query("
    SELECT a.id, a.note, a.commentaire, a.created_at,
           u.nom, u.prenom,
           t.depart, t.destination, t.date
    FROM avis a
    JOIN users u ON a.utilisateur_id = u.id
    JOIN trajets t ON a.trajet_id = t.id
    WHERE a.valide = 0
    ORDER BY a.created_at ASC
");
$avis_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';

$pseudo = $_SESSION['pseudo'] ?? 'Employé';
?>

<div class="container mt-5">
    <h2>👨‍💼 Bienvenue <?= htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8') ?> — Modération des avis</h2>
    <p>Depuis cet espace, vous pouvez gérer les avis laissés par les utilisateurs et superviser les trajets.</p>

    <?php if (!empty($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['erreur'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['erreur'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (empty($avis_attente)): ?>
        <p class="text-muted">Aucun avis en attente de validation.</p>
    <?php else: ?>
        <?php foreach ($avis_attente as $avis): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <p class="mb-1">
                        <strong><?= htmlspecialchars(($avis['prenom'] ?? '') . ' ' . ($avis['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        sur le trajet
                        <strong><?= htmlspecialchars($avis['depart'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        → <strong><?= htmlspecialchars($avis['destination'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        (<?= htmlspecialchars($avis['date'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                    </p>

                    <p class="mb-1"><strong>Note :</strong> <?= (int)($avis['note'] ?? 0) ?>/5</p>
                    <p class="mb-3"><strong>Commentaire :</strong><br><?= nl2br(htmlspecialchars($avis['commentaire'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>

                    <!-- On poste vers la même page -->
                    <form method="POST" action="/views/espace_employe.php" class="d-inline">
                        <input type="hidden" name="avis_id" value="<?= (int)$avis['id'] ?>">
                        <input type="hidden" name="action" value="valider">
                        <button type="submit" class="btn btn-success">✅ Valider</button>
                    </form>

                    <form method="POST" action="/views/espace_employe.php" class="d-inline" onsubmit="return confirm('Supprimer cet avis ?');">
                        <input type="hidden" name="avis_id" value="<?= (int)$avis['id'] ?>">
                        <input type="hidden" name="action" value="refuser">
                        <button type="submit" class="btn btn-danger">❌ Refuser</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="mt-4">
        <a href="/controllers/deconnexion.php" class="btn btn-secondary">🔓 Se déconnecter</a>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
