<?php
declare(strict_types=1);

ini_set('display_errors','1'); error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php'; // <- important

$src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$depart      = trim($src['depart']      ?? '');
$destination = trim($src['destination'] ?? '');
$date        = trim($src['date']        ?? '');

$filtreEco   = $src['filtreEco']   ?? '';
$filtrePrix  = $src['filtrePrix']  ?? '';
$filtreDuree = $src['filtreDuree'] ?? '';
$filtreNote  = $src['filtreNote']  ?? '';

if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format de date invalide. AAAA-MM-JJ attendu.']);
    exit;
}

try {
    $sql = "
        SELECT 
            t.id, t.depart, t.destination, t.date, t.prix, t.places, t.duree_minutes, t.chauffeur, t.eco,
            ROUND(AVG(CASE WHEN a.valide = 1 THEN a.note END), 1) AS note_chauffeur
        FROM trajets t
        LEFT JOIN avis a ON a.trajet_id = t.id
        WHERE 1=1
          AND LOWER(t.depart) LIKE LOWER(:depart)
          AND LOWER(t.destination) LIKE LOWER(:destination)
          AND t.statut = 'à_venir'
          AND t.places > 0
    ";
    $params = [
        ':depart'      => "%$depart%",
        ':destination' => "%$destination%",
    ];

    if ($date !== '') { $sql .= " AND DATE(t.date) = :date"; $params[':date'] = $date; }
    if ($filtreEco   !== '') { $sql .= " AND t.eco = :eco"; $params[':eco'] = (int)$filtreEco; }
    if ($filtrePrix  !== '') { $sql .= " AND t.prix <= :prixmax"; $params[':prixmax'] = (float)$filtrePrix; }
    if ($filtreDuree !== '') { $sql .= " AND t.duree_minutes <= :dureemax"; $params[':dureemax'] = (int)$filtreDuree; }

    $sql .= " GROUP BY t.id";
    if ($filtreNote !== '') { $sql .= " HAVING note_chauffeur >= :note_min"; $params[':note_min'] = (float)$filtreNote; }
    $sql .= " ORDER BY t.date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        if ($r['note_chauffeur'] === null) $r['note_chauffeur'] = 'N/A';
    }

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: '.$e->getMessage()]);
}
