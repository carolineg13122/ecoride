<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("config/database.php");
header('Content-Type: application/json');

$depart = $_GET['depart'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['date'] ?? '';
$filtreEco = $_GET['filtreEco'] ?? '';
$filtrePrix = $_GET['filtrePrix'] ?? '';
$filtreDuree = $_GET['filtreDuree'] ?? '';
$filtreNote = $_GET['filtreNote'] ?? '';

$sql = "SELECT 
            t.id, t.depart, t.destination, t.date, t.prix, t.places, t.duree_minutes, t.chauffeur, t.photo, t.eco,
            ROUND(AVG(CASE WHEN a.valide = 1 THEN a.note ELSE NULL END), 1) AS note_chauffeur
        FROM trajets t
        LEFT JOIN avis a ON a.trajet_id = t.id
        WHERE LOWER(t.depart) LIKE LOWER(:depart)
          AND LOWER(t.destination) LIKE LOWER(:destination)
          AND t.date = :date
          AND t.places > 0";

$params = [
    ':depart' => "%$depart%",
    ':destination' => "%$destination%",
    ':date' => $date
];

if ($filtreEco !== '') {
    $sql .= " AND t.eco = :eco";
    $params[':eco'] = $filtreEco;
}
if ($filtrePrix !== '') {
    $sql .= " AND t.prix <= :prixmax";
    $params[':prixmax'] = $filtrePrix;
}
if ($filtreDuree !== '') {
    $sql .= " AND t.duree_minutes <= :dureemax";
    $params[':dureemax'] = $filtreDuree;
}

$sql .= " GROUP BY t.id";

if ($filtreNote !== '') {
    $sql .= " HAVING note_chauffeur >= :note_min";
    $params[':note_min'] = $filtreNote;
}

$sql .= " ORDER BY t.date ASC";

try {
    file_put_contents("debug.txt", print_r([
        'sql' => $sql,
        'params' => $params,
        'GET' => $_GET
    ], true));
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trajets as &$t) {
    // On n'inclut pas la photo du chauffeur dans la recherche
    $t['photo_chauffeur'] = null;

    
        if (!isset($t['note_chauffeur'])) {
            $t['note_chauffeur'] = 'N/A';
        }
    }
    
    

    echo json_encode($trajets);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
