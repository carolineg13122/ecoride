<?php
require_once '../config/database.php';
session_start();

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

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->buffer($row['photo']);

header("Content-Type: $mime");
echo $row['photo'];
exit();
