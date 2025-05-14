<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['email'];
$password = $_POST['mot_de_passe'] ?? null;
$photo_blob = null;

if (!empty($_FILES['photo']['tmp_name'])) {
    $photo_blob = file_get_contents($_FILES['photo']['tmp_name']);
}

try {
    $sql = "UPDATE users SET nom = ?, prenom = ?, email = ?";
    $params = [$nom, $prenom, $email];

    if (!empty($password)) {
        $sql .= ", mot_de_passe = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($photo_blob !== null) {
        $sql .= ", photo = ?";
        $params[] = $photo_blob;
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $conn->prepare($sql);

    if ($photo_blob !== null) {
        foreach ($params as $i => $param) {
            $type = ($i === count($params) - 2) ? PDO::PARAM_LOB : PDO::PARAM_STR;
            $stmt->bindParam($i + 1, $params[$i], $type);
        }
        $stmt->execute();
    } else {
        $stmt->execute($params);
    }

    header("Location: espace_utilisateur.php?message=Profil mis à jour !");
    exit();

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
