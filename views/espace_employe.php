<?php
session_start();
require_once("../config/database.php");


// Vérifier si l'utilisateur est connecté et employé
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header("Location: ../controllers/connexion.php?message=Accès réservé aux employés.");
    exit();
}

// Traitement de la validation ou suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avis_id = $_POST['avis_id'];
    $action = $_POST['action'];

    if ($action === 'valider') {
        $stmt = $conn->prepare("UPDATE avis SET valide = 1 WHERE id = ?");
        $stmt->execute([$avis_id]);
    } elseif ($action === 'refuser') {
        $stmt = $conn->prepare("DELETE FROM avis WHERE id = ?");
        $stmt->execute([$avis_id]);
    }

    header("Location: ../views/espace_employe.php?message=Action effectuée");
    exit();
}

// Récupérer les avis à valider
$stmt = $conn->query("
    SELECT a.*, u.nom, u.prenom, t.depart, t.destination, t.date 
    FROM avis a
    JOIN users u ON a.utilisateur_id = u.id
    JOIN trajets t ON a.trajet_id = t.id
    WHERE a.valide = 0
    ORDER BY a.created_at ASC
");
$avis_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once("../templates/header.php"); ?>

<div class="container mt-5">
    <h2>👨‍💼 Espace Employé — Modération des avis</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <?php if (count($avis_attente) === 0): ?>
        <p class="text-muted">Aucun avis en attente de validation.</p>
    <?php else: ?>
        <?php foreach ($avis_attente as $avis): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <p><strong><?= htmlspecialchars($avis['prenom']) ?></strong> a laissé un avis sur le trajet :
                    <strong><?= htmlspecialchars($avis['depart']) ?> → <?= htmlspecialchars($avis['destination']) ?></strong>
                    (<?= htmlspecialchars($avis['date']) ?>)</p>

                    <p><strong>Note :</strong> <?= htmlspecialchars($avis['note']) ?>/5</p>
                    <p><strong>Commentaire :</strong><br><?= nl2br(htmlspecialchars($avis['commentaire'])) ?></p>

                    <form method="POST" class="d-inline">
                        <input type="hidden" name="avis_id" value="<?= $avis['id'] ?>">
                        <input type="hidden" name="action" value="valider">
                        <button type="submit" class="btn btn-success">✅ Valider</button>
                    </form>

                    <form method="POST" class="d-inline">
                        <input type="hidden" name="avis_id" value="<?= $avis['id'] ?>">
                        <input type="hidden" name="action" value="refuser">
                        <button type="submit" class="btn btn-danger">❌ Refuser</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php if (isset($_GET['signalement']) && $_GET['signalement'] === 'ok'): ?>
    <div class="alert alert-success">✅ Signalement traité avec succès.</div>
<?php endif; ?><?php if (isset($_GET['signalement']) && $_GET['signalement'] === 'deja_traite'): ?>
    <div class="alert alert-warning">⚠️ Ce signalement a déjà été traité.</div>
<?php endif; ?>


<?php require_once("../templates/footer.php"); ?>
