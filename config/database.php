<?php

$host     = getenv('DB_HOST') ?: 'db';
$port     = (int)(getenv('DB_PORT') ?: 3306);
$dbname   = getenv('DB_NAME') ?: 'ecoride';
$username = getenv('DB_USER') ?: 'user';
$password = getenv('DB_PASS') ?: 'password';
$charset  = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
  die("Erreur de connexion : " . $e->getMessage() . "<br><small>DSN: {$dsn} — User: {$username}</small>");
}
