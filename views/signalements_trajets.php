<?php
session_start();
require_once '../config/database.php';

// Vérification accès employé
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employe') {
    header("Location: ../controllers/connexion.php");
    exit();
}

// Récupérer les signalements "probleme"
$stmt = $conn->prepare("
    SELECT c.*, u.nom AS nom_passager, u.prenom AS prenom_passager,
           t.depart, t.destination, t.date, t.prix, t.user_id AS chauffeur_id
    FROM confirmations c
    JOIN users u ON c.id_passager = u.id
    JOIN trajets t ON c.id_trajet = t.id
    WHERE c.statut = 'probleme' AND c.valide = 0
");
$stmt->execute();
$signalements = $stmt->fetchAll();
?>
<?php require_once("../templates/header.php"); ?>
<div class="container mt-5">

<h2>🛑 Signalements de trajets</h2>

<?php foreach ($signalements as $s): ?>
    <div class="border p-3 mb-4">
        <h5>Trajet : <?= htmlspecialchars($s['depart']) ?> → <?= htmlspecialchars($s['destination']) ?> (<?= $s['date'] ?>)</h5>
        <p><strong>Passager :</strong> <?= htmlspecialchars($s['prenom_passager']) ?> <?= htmlspecialchars($s['nom_passager']) ?></p>
        <p><strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($s['commentaire'])) ?></p>

        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
    <!-- Créditer quand même -->
            <form action="../controllers/traiter_signalement.php" method="POST">
                <input type="hidden" name="trajet_id" value="<?= $s['id_trajet'] ?>">
                <input type="hidden" name="chauffeur_id" value="<?= $s['chauffeur_id'] ?>">
                <input type="hidden" name="confirmation_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="prix" value="<?= $s['prix'] ?>">
                <input type="hidden" name="action" value="crediter">
                <button type="submit" class="btn btn-success">✅ Créditer quand même</button>  
            </form>

    <!-- Ne pas créditer -->
            <form action="../controllers/traiter_signalement.php" method="POST" onsubmit="return confirm('Confirmer que le chauffeur ne sera pas crédité ?');">
                <input type="hidden" name="confirmation_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="action" value="refuser">
                <button type="submit" class="btn btn-outline-danger">🚫 Ne pas créditer</button>
            </form>

        </div>

    </div>

<?php endforeach; ?>
</div>

<?php require_once("../templates/footer.php"); ?>