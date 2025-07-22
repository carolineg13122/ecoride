<?php
require_once("config/database.php");
?>

<?php
$host = 'gondola.proxy.rlwy.net';
$port = 17584;
$dbname = 'railway';
$username = 'root';
$password = 'tZNJAeubqbOcOmUKmKYgKDdYPKIIhdEA';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    echo "✅ Connexion Fly.io → Railway OK";
} catch (PDOException $e) {
    echo "❌ Erreur Fly.io → Railway : " . $e->getMessage();
}
?>
