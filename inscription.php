<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>📝 Inscription</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <form action="traiter_inscription.php" method="POST" enctype="multipart/form-data">
        <div class="form-group mb-3">
            <label for="nom">Nom</label>
            <input type="text" class="form-control" name="nom" id="nom" required>
        </div>

        <div class="form-group mb-3">
            <label for="prenom">Prénom</label>
            <input type="text" class="form-control" name="prenom" id="prenom" required>
        </div>

        <div class="form-group mb-3">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" id="email" required>
        </div>
        <div class="form-group mb-3">
            <label for="photo" class="form-label">📸 Photo de profil (optionnel) :</label>
            <input type="file" name="photo" id="photo" class="form-control">
        </div>
        <div class="form-group mb-3">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" class="form-control" name="mot_de_passe" id="mot_de_passe" required>
        </div>

        <div class="form-group mb-3">
            <label for="confirmer_mot_de_passe">Confirmer le mot de passe</label>
            <input type="password" class="form-control" name="confirmer_mot_de_passe" id="confirmer_mot_de_passe" required>
        </div>

        <button type="submit" class="btn btn-primary">✅ S'inscrire</button>
    </form>

    <p class="mt-3">Déjà inscrit ? <a href="connexion.php">Connectez-vous ici</a></p>
</div>

<?php require_once("templates/footer.php"); ?>
