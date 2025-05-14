<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>🔑 Connexion</h2>

    <!-- Affichage d'un message s'il existe dans l'URL -->
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
    <?php endif; ?>

    <form action="traiter_connexion.php" method="POST">
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary mt-2">Se connecter</button>
    </form>

    <p class="mt-3">Pas encore inscrit ? <a href="inscription.php">Inscrivez-vous ici</a></p>
</div>

<?php require_once("templates/footer.php"); ?>
