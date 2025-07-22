<?php
if (!getenv("DATABASE_URL")) {
    die("❌ DATABASE_URL non défini.");
}

$url = parse_url(getenv("DATABASE_URL"));

$host = $url["host"];
$dbname = ltrim($url["path"], "/");
$user = $url["user"];
$pass = $url["pass"];
$port = $url["port"] ?? 5432;

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // echo "✅ Connexion réussie à PostgreSQL !";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage();
    exit;
}
?>
