<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php");
    exit();
}


$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_photo'])) {
    // Supprimer la photo de la base
    $stmt = $conn->prepare("UPDATE users SET photo = NULL WHERE id = ?");
    $stmt->execute([$user_id]);

    // Recharger les infos utilisateur après suppression
    header("Location: ../views/profil.php?photo_supprimee=1");

    exit();
}

// Récupérer les infos utilisateur pour affichage
$stmt = $conn->prepare("SELECT nom, prenom, email, photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$finfo = new finfo(FILEINFO_MIME_TYPE);
$photo_base64 = !empty($user['photo']) ? base64_encode($user['photo']) : null;
$mime_type = !empty($user['photo']) ? $finfo->buffer($user['photo']) : null;

$message = '';
if (isset($_GET['photo_supprimee']) && $_GET['photo_supprimee'] == 1) {
    $message = "✅ Votre photo de profil a été supprimée avec succès.";
}
?>
<?php require_once("../templates/header.php"); ?>


<h2 class="ms-4">✏️ Modifier mon profil</h2>
<?php if (!empty($message)): ?>
    <div class="alert alert-success ms-4"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row mt-4 gx-5 align-items-center">
    <div class="col-md-8 order-2 order-md-1 offset-md-1">
        <form action="modifier_profil.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom :</label>
                <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>">
            </div>
            <div class="mb-3">
                <label for="prenom" class="form-label">Prénom :</label>
                <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email :</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe (laisser vide pour ne pas modifier) :</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control">
            </div>
            <div class="mb-3">
                <label for="photo" class="form-label">Changer la photo de profil :</label>
                <input type="file" name="photo" id="photo" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
    <div class="col-md-3 text-center order-1 order-md-2 d-flex flex-column align-items-center justify-content-center">
        <h5>Photo actuelle</h5>
        <?php if (!empty($photo_base64) && !empty($mime_type)): ?>
            <img src="data:<?= $mime_type ?>;base64,<?= $photo_base64 ?>" alt="Photo de profil" class="img-thumbnail rounded-circle" width="150">
        <?php else: ?>
            <img src="../assets/images/OIP.jpg" class="img-thumbnail rounded-circle" width="150">
        <?php endif; ?>
        <form method="POST" class="mt-3" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?');">
    <input type="hidden" name="supprimer_photo" value="1">
    <button type="submit" class="btn btn-outline-danger btn-sm">🗑️ Supprimer la photo</button>
</form>

    </div>
</div>

<?php require_once("../templates/footer.php"); ?>

