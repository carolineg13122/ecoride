<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Vérif méthode
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header("Location: /views/contact.php?message=" . rawurlencode("Méthode invalide.") . "&type=warning");
    exit;
}

// CSRF
$csrf_ok = isset($_POST['csrf_token'], $_SESSION['csrf_contact']) && hash_equals($_SESSION['csrf_contact'], $_POST['csrf_token']);
if (!$csrf_ok) {
    header("Location: /views/contact.php?message=" . rawurlencode("Session expirée. Merci de réessayer.") . "&type=danger");
    exit;
}

// Récup & validations
$nom     = trim($_POST['nom']    ?? '');
$email   = trim($_POST['email']  ?? '');
$sujet   = trim($_POST['sujet']  ?? '');
$message = trim($_POST['message']?? '');

if ($nom === '' || $email === '' || $sujet === '' || $message === '') {
    header("Location: /views/contact.php?message=" . rawurlencode("Merci de remplir tous les champs.") . "&type=warning");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /views/contact.php?message=" . rawurlencode("Adresse email invalide.") . "&type=danger");
    exit;
}

// Construire le mail
$to       = "contact@ecoride.com";
$subject  = "[EcoRide] " . $sujet;
$body     = "De : {$nom} <{$email}>\n\nMessage :\n{$message}\n";
$headers  = [];
$headers[] = "From: {$nom} <{$email}>";
$headers[] = "Reply-To: {$email}";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "MIME-Version: 1.0";

$headers_str = implode("\r\n", $headers);

// Tentative d’envoi
$sent = @mail($to, $subject, $body, $headers_str);

// Fallback en local : log si mail() indisponible
if (!$sent) {
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
    $logFile = $logDir . '/contact.log';
    $stamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[{$stamp}] {$subject}\n{$body}\n----\n", FILE_APPEND);
}

// Invalide le token CSRF pour éviter double POST
unset($_SESSION['csrf_contact']);

$flash = $sent ? "Votre message a bien été envoyé. Merci !" : "Message enregistré. (Envoi email non disponible en local)";
$type  = $sent ? "success" : "info";
header("Location: /views/contact.php?message=" . rawurlencode($flash) . "&type={$type}");
exit;
