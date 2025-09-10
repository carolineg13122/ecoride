<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /controllers/connexion.php?message=" . urlencode("Veuillez vous connecter."));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: /views/mes_trajets.php?erreur=" . urlencode("Requête invalide."));
    exit;
}

$user_id        = (int) $_SESSION['user_id'];
$depart         = trim($_POST['depart'] ?? '');
$adresse_depart = trim($_POST['adresse_depart'] ?? '');
$destination    = trim($_POST['destination'] ?? '');
$adresse_arrivee= trim($_POST['adresse_arrivee'] ?? '');
$date           = trim($_POST['date'] ?? '');
$prix           = $_POST['prix'] ?? '';
$places         = $_POST['places'] ?? '';
$duree_minutes  = $_POST['duree_minutes'] ?? 0;

$vehicule_id    = !empty($_POST['vehicule_id']) ? (int)$_POST['vehicule_id'] : null;

/* Champs pour un nouveau véhicule */
$marque     = trim($_POST['marque'] ?? '');
$modele     = trim($_POST['modele'] ?? '');
$couleur    = trim($_POST['couleur'] ?? '');
$energie    = trim($_POST['energie'] ?? '');
$plaque     = trim($_POST['plaque_immatriculation'] ?? '');
$date_immat = trim($_POST['date_immatriculation'] ?? '');

/* Validations */
$errors = [];
if ($depart === '' || $destination === '' || $date === '' || $prix === '' || $places === '') {
    $errors[] = "Tous les champs obligatoires doivent être remplis.";
}
if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errors[] = "Format de date trajet invalide (AAAA-MM-JJ).";
}
if (!is_numeric($prix) || (float)$prix < 0) $errors[] = "Prix invalide.";
if (!ctype_digit((string)$places) || (int)$places < 1) $errors[] = "Nombre de places invalide (>= 1).";
if (!ctype_digit((string)$duree_minutes) || (int)$duree_minutes < 1) $errors[] = "Durée estimée invalide.";

if (!$vehicule_id && ($marque==='' || $modele==='' || $energie==='' || $plaque==='' || $date_immat==='')) {
    $errors[] = "Vous devez choisir un véhicule existant OU en ajouter un complet.";
}
if ($errors) {
    header("Location: /views/mes_trajets.php?erreur=" . urlencode(implode(' ', $errors)));
    exit;
}

try {
    // Vérif crédits utilisateur
    $stmt = $conn->prepare("SELECT prenom, nom, credits FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u || $u['credits'] < 2) {
        header("Location: /views/mes_trajets.php?erreur=" . urlencode("Crédits insuffisants (2 requis)."));
        exit;
    }

    $chauffeur_nom = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));

    $conn->beginTransaction();

    // Si pas de véhicule sélectionné, on en crée un
    if (!$vehicule_id) {
        $stmt = $conn->prepare("
            INSERT INTO vehicules (user_id, marque, modele, couleur, energie, plaque_immatriculation, date_immatriculation)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $marque, $modele, $couleur, $energie, $plaque, $date_immat]);
        $vehicule_id = (int) $conn->lastInsertId();
    }

    // Insertion du trajet
    $stmt = $conn->prepare("
        INSERT INTO trajets (
            user_id, chauffeur, vehicule_id, depart, adresse_depart, destination, adresse_arrivee,
            date, prix, places, duree_minutes, statut
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'à_venir')
    ");
    $stmt->execute([
        $user_id, $chauffeur_nom, $vehicule_id,
        $depart, $adresse_depart, $destination, $adresse_arrivee,
        $date, $prix, $places, $duree_minutes
    ]);

    // Déduire crédits
    $stmt = $conn->prepare("UPDATE users SET credits = credits - 2 WHERE id=?");
    $stmt->execute([$user_id]);

    $conn->commit();

    header("Location: /views/mes_trajets.php?message=" . urlencode("Trajet ajouté avec succès."));
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header("Location: /views/mes_trajets.php?erreur=" . urlencode("Erreur: " . $e->getMessage()));
    exit;
}
