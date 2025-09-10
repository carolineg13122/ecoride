<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php");
    exit;
}

// CSRF token (servira aux deux formulaires)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = (int) $_SESSION['user_id'];

// Suppression de la photo (POST dédié)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['supprimer_photo'])) {
    // Vérifier CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: ../views/profil.php?erreur=" . urlencode("Session expirée, réessayez."));
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET photo = NULL WHERE id = ?");
    $stmt->execute([$user_id]);

    header("Location: ../views/profil.php?message=" . urlencode("✅ Votre photo de profil a été supprimée avec succès."));
    exit;
}

// Récupération profil
$stmt = $conn->prepare("SELECT nom, prenom, email, photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../controllers/connexion.php?message=" . urlencode("Session invalide, veuillez vous reconnecter."));
    exit;
}

// Préparer l’affichage de la photo
$photo_base64 = null;
$mime_type    = null;
if (!empty($user['photo'])) {
    $photo_base64 = base64_encode($user['photo']);
    if (class_exists('finfo')) {
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($user['photo']) ?: 'image/jpeg';
    } else {
        $mime_type = 'image/jpeg';
    }
}

$message = $_GET['message'] ?? '';
$erreur  = $_GET['erreur']  ?? '';

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
  <h2>✏️ Modifier mon profil</h2>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="row mt-4 gx-5 align-items-start">
    <!-- Formulaire d'édition -->
    <div class="col-md-8 order-2 order-md-1">
      <form action="../views/modifier_profil.php" method="POST" enctype="multipart/form-data" autocomplete="on">
        <!-- CSRF & taille max indicative -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="3145728"><!-- 3 Mo -->

        <div class="mb-3">
          <label for="nom" class="form-label">Nom :</label>
          <input type="text" name="nom" id="nom" class="form-control"
                 value="<?= htmlspecialchars($user['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 autocomplete="family-name" required>
        </div>

        <div class="mb-3">
          <label for="prenom" class="form-label">Prénom :</label>
          <input type="text" name="prenom" id="prenom" class="form-control"
                 value="<?= htmlspecialchars($user['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 autocomplete="given-name" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email :</label>
          <input type="email" name="email" id="email" class="form-control"
                 value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 autocomplete="email" inputmode="email" required>
        </div>

        <div class="mb-3">
          <label for="mot_de_passe" class="form-label">Mot de passe (laisser vide pour ne pas modifier) :</label>
          <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control"
                 autocomplete="new-password" minlength="8">
        </div>

        <div class="mb-3">
          <label for="photo" class="form-label">Changer la photo de profil :</label>
          <input type="file" name="photo" id="photo" class="form-control"
                 accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
          <small class="form-text text-muted">Formats acceptés : JPG / PNG / WebP — 3 Mo max.</small>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </form>
    </div>

    <!-- Encadré photo actuelle -->
    <div class="col-md-4 order-1 order-md-2 text-center d-flex flex-column align-items-center">
      <h5>Photo actuelle</h5>
      <?php if (!empty($photo_base64) && !empty($mime_type)): ?>
        <img src="data:<?= htmlspecialchars($mime_type, ENT_QUOTES, 'UTF-8') ?>;base64,<?= $photo_base64 ?>"
             alt="Photo de profil"
             class="img-thumbnail rounded-circle mb-3" width="150">
      <?php else: ?>
        <img src="../assets/images/OIP.jpg" class="img-thumbnail rounded-circle mb-3" width="150" alt="Photo par défaut">
      <?php endif; ?>

      <form method="POST" class="mt-1"
            onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="supprimer_photo" value="1">
        <button type="submit" class="btn btn-outline-danger btn-sm">🗑️ Supprimer la photo</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
