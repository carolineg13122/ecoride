<?php require_once("../templates/header.php"); ?>

<div class="container mt-5">
    <h2>📬 Contactez-nous</h2>

    <p>Vous pouvez nous joindre par les moyens suivants :</p>

    <ul>
        <li><strong>Email :</strong> contact@ecoride.com</li>
        <li><strong>Téléphone :</strong> 01 23 45 67 89</li>
        <li><strong>Adresse :</strong> 123 Rue de la Mobilité, 75000 Paris</li>
    </ul>

    <hr>

    <h4>📝 Envoyez-nous un message</h4>
    <form action="#" method="POST">
        <div class="form-group mb-2">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" class="form-control" required>
        </div>

        <div class="form-group mb-2">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>

        <div class="form-group mb-2">
            <label for="sujet">Sujet</label>
            <input type="text" id="sujet" name="sujet" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label for="message">Message</label>
            <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">📤 Envoyer</button>
    </form>
</div>

<?php require_once("../templates/footer.php"); ?>
