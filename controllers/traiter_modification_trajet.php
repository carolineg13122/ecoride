<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

/* --- Auth --- */
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php');
    exit;
}

/* --- Méthode --- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /views/mes_trajets.php');
    exit;
}

/* --- Données --- */
$user_id         = (int) $_SESSION['user_id'];
$trajet_id       = isset($_POST['trajet_id']) ? (int)$_POST['trajet_id'] : 0;

$depart          = trim($_POST['depart']          ?? '');
$adresse_depart  = trim($_POST['adresse_depart']  ?? '');
$destination     = trim($_POST['destination']     ?? '');
$adresse_arrivee = trim($_POST['adresse_arrivee'] ?? '');
$date            = trim($_POST['date']            ?? '');
$prix            = (float)($_POST['prix']         ?? 0);
$places          = (int)  ($_POST['places']       ?? 0);
$preferences     = trim($_POST['preferences']     ?? '');
$fumeur          = isset($_POST['fumeur']) ? (int)!!$_POST['fumeur'] : 0;
$eco             = isset($_POST['eco'])    ? (int)!!$_POST['eco']    : 0;
$duree_minutes   = (int)  ($_POST['duree_minutes']?? 0);

/* --- Validations minimales --- */
if ($trajet_id <= 0 || $depart === '' || $adresse_depart === '' || $destination === '' || $adresse_arrivee === '' || $date === '' || $places < 1 || $duree_minutes < 1 || $prix < 0) {
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Champs manquants ou invalides.'));
    exit;
}

try {
    /* Vérifier que le trajet appartient à l'utilisateur et statut modifiable */
    $stmt = $conn->prepare('SELECT statut FROM trajets WHERE id = ? AND user_id = ?');
    $stmt->execute([$trajet_id, $user_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet introuvable ou non autorisé.'));
        exit;
    }
    if (($trajet['statut'] ?? '') === 'termine') {
        header('Location: /views/mes_trajets.php?message=' . rawurlencode('Un trajet terminé ne peut plus être modifié.'));
        exit;
    }

    /* Mise à jour */
    $sql = 'UPDATE trajets
            SET depart = ?, adresse_depart = ?, destination = ?, adresse_arrivee = ?, date = ?, prix = ?, places = ?, preferences = ?, fumeur = ?, eco = ?, duree_minutes = ?
            WHERE id = ? AND user_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $depart,
        $adresse_depart,
        $destination,
        $adresse_arrivee,
        $date,            // adapte si ta colonne est DATETIME (YYYY-MM-DD HH:MM:SS)
        $prix,
        $places,
        $preferences,
        $fumeur,
        $eco,
        $duree_minutes,
        $trajet_id,
        $user_id,
    ]);

    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet modifié avec succès !'));
    exit;

} catch (Throwable $e) {
    // En prod, log: $e->getMessage()
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Erreur serveur, modification non appliquée.'));
    exit;
}
