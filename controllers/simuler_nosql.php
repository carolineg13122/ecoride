<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$avis = [
    'utilisateur' => $_SESSION['prenom'] ?? 'Inconnu',
    'trajet_id'   => $_POST['trajet_id'] ?? 0,
    'note'        => $_POST['note'] ?? null,
    'commentaire' => $_POST['commentaire'] ?? '',
    'timestamp'   => date('Y-m-d H:i:s')
];

// Créer le dossier si nécessaire
if (!is_dir(__DIR__ . '/../nosql')) {
    mkdir(__DIR__ . '/../nosql', 0777, true);
}

// Ajouter au fichier JSON (append)
$fichier = __DIR__ . '/../nosql/avis.json';
$donnees = [];

if (file_exists($fichier)) {
    $json = file_get_contents($fichier);
    $donnees = json_decode($json, true) ?? [];
}

$donnees[] = $avis;

file_put_contents($fichier, json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Avis enregistré dans le fichier NoSQL (simulation)";
