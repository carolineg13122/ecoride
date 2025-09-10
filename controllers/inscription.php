<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("../templates/header.php");

/* (1) Rediriger si déjà connecté (optionnel) */
if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php?message=' . urlencode('Vous êtes déjà connecté.'));
    exit;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// helper préremplissage
function old($key) { return htmlspecialchars($_GET[$key] ?? '', ENT_QUOTES, 'UTF-8'); }

$message = $_GET['message'] ?? '';
$erreur  = $_GET['erreur']  ?? '';
?>
<div class="container mt-5">
    <h2>📝 Inscription</h2>

    <?php if ($message): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($erreur): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form action="../controllers/traiter_inscription.php" method="POST" enctype="multipart/form-data" autocomplete="on" id="form-inscription">
        <!-- CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <!-- Taille max indicative (vérifier aussi côté serveur) -->
        <input type="hidden" name="MAX_FILE_SIZE" value="3145728"><!-- 3 Mo -->

        <div class="form-group mb-3">
            <label for="nom">Nom</label>
            <input type="text" class="form-control" name="nom" id="nom" required
                   autocomplete="family-name" value="<?= old('nom') ?>">
        </div>

        <div class="form-group mb-3">
            <label for="prenom">Prénom</label>
            <input type="text" class="form-control" name="prenom" id="prenom" required
                   autocomplete="given-name" value="<?= old('prenom') ?>">
        </div>

        <div class="form-group mb-3">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" id="email" required
                   autocomplete="email" inputmode="email" value="<?= old('email') ?>">
        </div>

        <div class="form-group mb-3">
            <label for="photo" class="form-label">📸 Photo de profil (optionnel)</label>
            <input type="file" name="photo" id="photo" class="form-control"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <small class="form-text text-muted">JPG/PNG/WebP, 3 Mo max.</small>
            <!-- (3) Aperçu visuel optionnel -->
            <img id="preview" alt="" class="mt-2 d-none rounded" style="max-height:120px;">
        </div>

        <div class="form-group mb-3">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" class="form-control" name="mot_de_passe" id="mot_de_passe" required
                   autocomplete="new-password" minlength="8"
                   placeholder="Au moins 8 caractères">
        </div>

        <div class="form-group mb-3">
            <label for="confirmer_mot_de_passe">Confirmer le mot de passe</label>
            <input type="password" class="form-control" name="confirmer_mot_de_passe" id="confirmer_mot_de_passe" required
                   autocomplete="new-password" minlength="8">
        </div>

        <button type="submit" class="btn btn-primary">✅ S'inscrire</button>
    </form>

    <p class="mt-3">Déjà inscrit ? <a href="connexion.php">Connectez-vous ici</a></p>
</div>

<script>
// (2) Alerte taille > 3 Mo + (3) aperçu image
document.getElementById('photo')?.addEventListener('change', function() {
  const file = this.files?.[0];
  const preview = document.getElementById('preview');
  if (!file) { if (preview){ preview.classList.add('d-none'); preview.src=''; } return; }

  if (file.size > 3 * 1024 * 1024) {
    alert('Fichier trop volumineux (max 3 Mo).');
    this.value = '';
    if (preview){ preview.classList.add('d-none'); preview.src=''; }
    return;
  }

  if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) {
    alert('Format non supporté. Utilisez JPG/PNG/WebP.');
    this.value = '';
    if (preview){ preview.classList.add('d-none'); preview.src=''; }
    return;
  }

  const reader = new FileReader();
  reader.onload = e => {
    if (preview) {
      preview.src = e.target.result;
      preview.classList.remove('d-none');
    }
  };
  reader.readAsDataURL(file);
});
</script>

<?php require_once("../templates/footer.php"); ?>
