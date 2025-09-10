<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /controllers/connexion.php");
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: /views/profil.php");
    exit;
}

/* CSRF */
$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    header("Location: /views/profil.php?erreur=" . urlencode("Session expirée, merci de réessayer."));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* Récup champs */
$nom     = trim($_POST['nom']     ?? '');
$prenom  = trim($_POST['prenom']  ?? '');
$email   = trim($_POST['email']   ?? '');
$pass    = $_POST['mot_de_passe'] ?? '';

/* Validations */
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /views/profil.php?erreur=" . urlencode("Email invalide."));
    exit;
}
if ($pass !== '' && strlen($pass) < 8) {
    header("Location: /views/profil.php?erreur=" . urlencode("Mot de passe trop court (8 caractères min.)."));
    exit;
}

/* Unicité email si modifié */
if ($email !== '') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        header("Location: /views/profil.php?erreur=" . urlencode("Cet email est déjà utilisé par un autre compte."));
        exit;
    }
}

/* Photo optionnelle */
$photoBlob = null;
if (!empty($_FILES['photo']['name'])) {
    $err = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        header("Location: /views/profil.php?erreur=" . urlencode("Échec de l’upload de la photo (code $err)."));
        exit;
    }
    $maxSize = 3 * 1024 * 1024; // 3 Mo
    if (($_FILES['photo']['size'] ?? 0) > $maxSize) {
        header("Location: /views/profil.php?erreur=" . urlencode("Photo trop volumineuse (max 3 Mo)."));
        exit;
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
        header("Location: /views/profil.php?erreur=" . urlencode("Format d’image non supporté (JPEG/PNG/WebP)."));
        exit;
    }
    $photoBlob = file_get_contents($tmp);
}

/* Construction dynamique de l’UPDATE */
$sets   = [];
$params = [];

if ($nom !== '')        { $sets[] = "nom = :nom";           $params[':nom']    = $nom; }
if ($prenom !== '')     { $sets[] = "prenom = :prenom";     $params[':prenom'] = $prenom; }
if ($email !== '')      { $sets[] = "email = :email";       $params[':email']  = strtolower($email); }
if ($pass !== '')       { $sets[] = "mot_de_passe = :pass"; $params[':pass']   = password_hash($pass, PASSWORD_DEFAULT); }
if ($photoBlob !== null){ $sets[] = "photo = :photo"; /* bind plus bas en LOB */ }

if (empty($sets)) {
    header("Location: /views/profil.php?message=" . urlencode("Aucune modification à enregistrer."));
    exit;
}

$sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";
$stmt = $conn->prepare($sql);

/* Bind */
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
if ($photoBlob !== null) {
    $stmt->bindValue(':photo', $photoBlob, PDO::PARAM_LOB);
}
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);

/* Exec */
try {
    $stmt->execute();
    header("Location: /views/profil.php?message=" . urlencode("Profil mis à jour avec succès."));
    exit;
} catch (Throwable $e) {
    header("Location: /views/profil.php?erreur=" . urlencode("Erreur lors de la mise à jour : " . $e->getMessage()));
    exit;
}
