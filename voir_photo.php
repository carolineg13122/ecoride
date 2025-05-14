<?php
require_once 'config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    exit('ID manquant');
}

$stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['photo'])) {
    http_response_code(404);
    exit('Image non trouvée');
}

header("Content-Type: image/jpeg");
echo $row['photo'];
