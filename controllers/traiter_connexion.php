<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: /controllers/connexion.php'); exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['mot_de_passe'] ?? ($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
  $_SESSION['flash_error'] = "Veuillez renseigner l'email et le mot de passe.";
  $_SESSION['debug_login'] = "Champs vides (email='$email').";
  header('Location: /controllers/connexion.php'); exit;
}

try {
  $st = $conn->prepare("SELECT id, email, role, mot_de_passe FROM users WHERE email = :email LIMIT 1");
  $st->execute([':email' => $email]);
  $user = $st->fetch();
} catch (Throwable $e) {
  $_SESSION['flash_error'] = "Erreur serveur.";
  $_SESSION['debug_login'] = "SQL error: ".$e->getMessage();
  header('Location: /controllers/connexion.php'); exit;
}

if (!$user) {
  $_SESSION['flash_error'] = "Email introuvable.";
  $_SESSION['debug_login'] = "Aucun user pour '$email'.";
  header('Location: /controllers/connexion.php'); exit;
}

$hash = (string)$user['mot_de_passe'];
$verify = password_verify($pass, $hash);

if (!$verify) {
  $_SESSION['flash_error'] = "Mot de passe incorrect.";
  $_SESSION['debug_login'] = "Hash présent (".strlen($hash)." chars), verify=false.";
  header('Location: /controllers/connexion.php'); exit;
}

/* Connexion OK */
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = strtolower($user['role'] ?? 'utilisateur');
unset($_SESSION['debug_login']); // propre

switch ($_SESSION['role']) {
  case 'admin':     header('Location: /views/accueil_admin.php'); break;
  case 'employe':   header('Location: /views/accueil_employe.php'); break;
  default:          header('Location: /views/espace_utilisateur.php'); break;
}
exit;
