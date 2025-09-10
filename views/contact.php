<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once("../templates/header.php");

// CSRF token
if (empty($_SESSION['csrf_contact'])) {
    $_SESSION['csrf_contact'] = bin2hex(random_bytes(32));
}

// message flash
$flash = $_GET['message'] ?? '';
$flash_type = $_GET['type'] ?? 'info';
?>
<div class="container mt-5">
    <h2>📬 Contactez-nous</h2>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash_type) ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <p>Vous pouvez nous joindre par les moyens suivants :</p>
    <ul>
        <li><strong>Email :</strong> contact@ecoride.com</li>
        <li><strong>Téléphone :</strong> 01 23 45 67 89</li>
        <li><strong>Adresse :</strong> 123 Rue de la Mobilité, 75000 Paris</li>
    </ul>

    <hr>

    <h4>📝 Envoyez-nous un message</h4>
    <form action="../controllers/traiter_contact.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_contact']) ?>">

        <div class="form-group mb-2">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" class="form-control" required>
        </div>

        <div class="form-group mb-2">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" class="form-control" inputmode="email" autocomplete="email" required>
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
