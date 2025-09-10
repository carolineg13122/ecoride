<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /controllers/connexion.php?message=" . rawurlencode("Veuillez vous connecter."));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* Données reçues */
$nom     = trim($_POST['nom']    ?? '');
$prenom  = trim($_POST['prenom'] ?? '');
$email   = trim($_POST['email']  ?? '');
$passwd  = $_POST['mot_de_passe'] ?? null;

/* Fichier (optionnel) */
$photo_blob = null;
if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
    // gardes-fous simples
    $max = 3 * 1024 * 1024; // 3 Mo
    if (filesize($_FILES['photo']['tmp_name']) > $max) {
        header("Location: /views/profil.php?message=" . rawurlencode("Image trop lourde (3 Mo max)."));
        exit;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['photo']['tmp_name']) ?: '';
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
        header("Location: /views/profil.php?message=" . rawurlencode("Format d'image invalide (jpg/png/webp)."));
        exit;
    }
    $photo_blob = file_get_contents($_FILES['photo']['tmp_name']);
}

/* Construit la mise à jour dynamiquement (on ne met à jour que ce qui est renseigné) */
$sets   = [];
$params = [];

if ($nom !== '')    { $sets[] = 'nom = ?';    $params[] = $nom; }
if ($prenom !== '') { $sets[] = 'prenom = ?'; $params[] = $prenom; }

if ($email !== '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: /views/profil.php?message=" . rawurlencode("Email invalide."));
        exit;
    }
    // Unicité email (autres utilisateurs)
    $check = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ?");
    $check->execute([$email, $user_id]);
    if ($check->fetchColumn()) {
        header("Location: /views/profil.php?message=" . rawurlencode("Cet email est déjà utilisé par un autre compte."));
        exit;
    }
    $sets[] = 'email = ?'; 
    $params[] = $email;
}

if (!empty($passwd)) {
    if (strlen($passwd) < 8) {
        header("Location: /views/profil.php?message=" . rawurlencode("Mot de passe trop court (8 caractères min)."));
        exit;
    }
    $sets[] = 'mot_de_passe = ?';
    $params[] = password_hash($passwd, PASSWORD_DEFAULT);
}

if ($photo_blob !== null) {
    $sets[] = 'photo = ?';
    $params[] = $photo_blob; // PDO::PARAM_LOB géré automatiquement sur execute
}

if (empty($sets)) {
    header("Location: /views/espace_utilisateur.php?message=" . rawurlencode("Aucune donnée à modifier."));
    exit;
}

$sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?";
$params[] = $user_id;

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Si nom/prénom/email changent, mets à jour la session pour cohérence UI (facultatif)
    if ($nom    !== '') $_SESSION['nom']    = $nom;
    if ($prenom !== '') $_SESSION['prenom'] = $prenom;
    if ($email  !== '') $_SESSION['email']  = $email;

    header("Location: /views/espace_utilisateur.php?message=" . rawurlencode("Profil mis à jour !"));
    exit;

} catch (Throwable $e) {
    header("Location: /views/profil.php?message=" . rawurlencode("Erreur serveur, réessayez."));
    exit;
}
