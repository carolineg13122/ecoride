<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

function back_with(string $erreur = '', array $keep = []): void {
    // retour vers controllers/inscription.php
    $q = ['erreur' => $erreur] + $keep;
    header('Location: ' . '/controllers/inscription.php?' . http_build_query($q));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /controllers/inscription.php');
    exit;
}

/* CSRF : si tu l’as mis dans ton formulaire */
if (!empty($_SESSION['csrf_token'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        back_with("Session expirée. Merci de réessayer.");
    }
}

/* Récup + validations */
$nom    = trim($_POST['nom']    ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$email  = strtolower(trim($_POST['email'] ?? ''));
$pass   = $_POST['mot_de_passe'] ?? '';
$pass2  = $_POST['confirmer_mot_de_passe'] ?? '';

if ($nom === '' || $prenom === '' || $email === '' || $pass === '' || $pass2 === '') {
    back_with("Tous les champs sont obligatoires.", compact('nom','prenom','email'));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_with("Email invalide.", compact('nom','prenom','email'));
}
if (strlen($pass) < 8) {
    back_with("Mot de passe trop court (8 caractères minimum).", compact('nom','prenom','email'));
}
if ($pass !== $pass2) {
    back_with("Les mots de passe ne correspondent pas.", compact('nom','prenom','email'));
}

/* Unicité email */
$stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn()) {
    back_with("Email déjà utilisé.", compact('nom','prenom','email'));
}

/* Photo (optionnelle) */
$photoBlob = null;
if (!empty($_FILES['photo']['name'])) {
    $err = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        back_with("Échec de l’upload de la photo (code $err).", compact('nom','prenom','email'));
    }
    $maxSize = 3 * 1024 * 1024; // 3 Mo
    if (($_FILES['photo']['size'] ?? 0) > $maxSize) {
        back_with("Photo trop volumineuse (max 3 Mo).", compact('nom','prenom','email'));
    }
    $tmp = $_FILES['photo']['tmp_name'];
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $tmp);
        finfo_close($finfo);
    }
    $allowed = ['image/jpeg','image/png','image/webp'];
    if ($mime && !in_array($mime, $allowed, true)) {
        back_with("Format d’image non supporté (JPEG/PNG/WebP).", compact('nom','prenom','email'));
    }
    $photoBlob = file_get_contents($tmp);
}

/* Hash du mot de passe */
$mot_de_passe_hash = password_hash($pass, PASSWORD_DEFAULT);

/* Insertion */
try {
    $stmt = $conn->prepare("
        INSERT INTO users (nom, prenom, email, mot_de_passe, photo, credits, role, created_at)
        VALUES (:nom, :prenom, :email, :mot_de_passe, :photo, :credits, :role, NOW())
    ");
    $stmt->bindValue(':nom',          $nom);
    $stmt->bindValue(':prenom',       $prenom);
    $stmt->bindValue(':email',        $email);
    $stmt->bindValue(':mot_de_passe', $mot_de_passe_hash);
    if ($photoBlob === null) {
        $stmt->bindValue(':photo', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':photo', $photoBlob, PDO::PARAM_LOB);
    }
    $stmt->bindValue(':credits', 20, PDO::PARAM_INT);   // crédits de départ
    $stmt->bindValue(':role', 'utilisateur');           // rôle par défaut
    $stmt->execute();

    // Succès → redirection vers connexion
    header("Location: /controllers/connexion.php?message=" . urlencode("Inscription réussie. Vous pouvez vous connecter."));
    exit;

} catch (Throwable $e) {
    back_with("Erreur lors de l’inscription : " . $e->getMessage(), compact('nom','prenom','email'));
}
