<?php
$host = "localhost";
$dbname = "ecoride";
$username = "root";  // Par défaut sous XAMPP / MAMP
$password = "";  // Laisse vide sous XAMPP

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
