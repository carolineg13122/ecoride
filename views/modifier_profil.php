<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$nom = $_POST['nom'] ?? null;
$prenom = $_POST['prenom'] ?? null;
$email = $_POST['email'] ?? null;
$password = $_POST['mot_de_passe'] ?? null;
$photo_blob = null;

if (!empty($_FILES['photo']['tmp_name'])) {
    $photo_blob = file_get_contents($_FILES['photo']['tmp_name']);
}

try {
    $sql = "UPDATE users SET";
    $params = [];

    if ($nom) {
        $sql .= " nom = ?,";
        $params[] = $nom;
    }
    if ($prenom) {
        $sql .= " prenom = ?,";
        $params[] = $prenom;
    }
    if ($email) {
        $sql .= " email = ?,";
        $params[] = $email;
    }
    if (!empty($password)) {
        $sql .= " mot_de_passe = ?,";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    if ($photo_blob !== null) {
        $sql .= " photo = ?,";
        $params[] = $photo_blob;
    }

    // Supprimer la virgule finale
    $sql = rtrim($sql, ',');
    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $conn->prepare($sql);

    foreach ($params as $i => $param) {
        $type = (is_string($param) ? PDO::PARAM_STR : PDO::PARAM_LOB);
        $stmt->bindParam($i + 1, $params[$i], $type);
    }

    $stmt->execute();
    header("Location: ../views/espace_utilisateur.php?message=Profil mis à jour");
    exit();

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
