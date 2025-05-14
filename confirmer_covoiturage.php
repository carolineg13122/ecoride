<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$trajet_id = $_GET['id'] ?? null;

if (!$trajet_id) {
    $redirect = ($_SESSION['role'] === 'employe') ? 'espace_employe.php' : 'espace_utilisateur.php';
    header("Location: $redirect?erreur=id_manquant");
    exit();
}


// Insère ou met à jour la confirmation
$stmt = $conn->prepare("SELECT * FROM confirmations WHERE id_trajet = ? AND id_passager = ?");
$stmt->execute([$trajet_id, $user_id]);
$exist = $stmt->fetch();

if ($exist) {
    $update = $conn->prepare("UPDATE confirmations SET valide = 1, statut = 'ok' WHERE id_trajet = ? AND id_passager = ?");
    $update->execute([$trajet_id, $user_id]);
} else {
    $insert = $conn->prepare("INSERT INTO confirmations (id_trajet, id_passager, valide, statut) VALUES (?, ?, 1, 'ok')");
    $insert->execute([$trajet_id, $user_id]);
}

$redirect = ($_SESSION['role'] === 'employe') ? 'espace_employe.php' : 'espace_utilisateur.php';
header("Location: $redirect?confirmation=ok");

exit();
?>
