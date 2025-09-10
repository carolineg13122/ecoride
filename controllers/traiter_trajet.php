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
    header('Location: /views/espace_utilisateur.php');
    exit;
}

/* --- Récupération & validation minimale des champs --- */
$user_id          = (int) $_SESSION['user_id'];
$depart           = trim($_POST['depart']           ?? '');
$adresse_depart   = trim($_POST['adresse_depart']   ?? '');
$destination      = trim($_POST['destination']      ?? '');
$adresse_arrivee  = trim($_POST['adresse_arrivee']  ?? '');
$date             = trim($_POST['date']             ?? '');
$prix             = (float) ($_POST['prix']         ?? 0);
$places           = (int)   ($_POST['places']       ?? 0);
$duree_minutes    = (int)   ($_POST['duree_minutes']?? 0);
$preferences      = trim($_POST['preferences']      ?? '');
$fumeur           = isset($_POST['fumeur']) ? (int)!!$_POST['fumeur'] : 0;
$eco              = isset($_POST['eco'])    ? (int)!!$_POST['eco']    : 0;
$vehicule_id      = !empty($_POST['vehicule_id']) ? (int)$_POST['vehicule_id'] : null;

/* Champs indispensables */
if ($depart === '' || $adresse_depart === '' || $destination === '' || $adresse_arrivee === '' || $date === '' || $prix < 0 || $places < 1 || $duree_minutes < 1) {
    header('Location: /views/ajouter_trajet.php?message=' . rawurlencode('Merci de remplir tous les champs obligatoires.'));
    exit;
}

try {
    /* --- Vérifier crédits & récupérer identité chauffeur --- */
    $stmt_user = $conn->prepare('SELECT prenom, nom, credits FROM users WHERE id = ?');
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: /views/ajouter_trajet.php?message=' . rawurlencode('Utilisateur introuvable.'));
        exit;
    }
    if ((int)$user['credits'] < 2) {
        header('Location: /views/mes_trajets.php?message=' . rawurlencode("Vous n'avez pas assez de crédits pour publier un trajet."));
        exit;
    }

    $chauffeur = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));

    /* --- Transaction : véhicule (si nécessaire) + trajet + débit crédits --- */
    $conn->beginTransaction();

    if ($vehicule_id === null) {
        // Création d'un nouveau véhicule si aucun véhicule existant choisi
        $marque               = trim($_POST['marque']                ?? '');
        $modele               = trim($_POST['modele']                ?? '');
        $energie              = trim($_POST['energie']               ?? '');
        $plaque               = trim($_POST['plaque_immatriculation']?? '');
        $date_immatriculation = trim($_POST['date_immatriculation']  ?? '');

        // Tu peux ajouter ici des validations simples (ex: plaque non vide si on crée un véhicule)
        $sql_vehicule = 'INSERT INTO vehicules (user_id, marque, modele, energie, plaque_immatriculation, date_immatriculation)
                         VALUES (?, ?, ?, ?, ?, ?)';
        $stmt_vehicule = $conn->prepare($sql_vehicule);
        $stmt_vehicule->execute([$user_id, $marque, $modele, $energie, $plaque, $date_immatriculation]);
        $vehicule_id = (int)$conn->lastInsertId();
    }

    /* --- Insertion du trajet --- */
    $sql_trajet = 'INSERT INTO trajets 
        (user_id, chauffeur, vehicule_id, depart, adresse_depart, destination, adresse_arrivee, date, prix, places, preferences, fumeur, eco, duree_minutes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt_trajet = $conn->prepare($sql_trajet);
    $stmt_trajet->execute([
        $user_id,
        $chauffeur,
        $vehicule_id,
        $depart,
        $adresse_depart,
        $destination,
        $adresse_arrivee,
        $date,          // si ta colonne est DATETIME, envoie au bon format 'YYYY-MM-DD HH:MM:SS'
        $prix,
        $places,
        $preferences,
        $fumeur,
        $eco,
        $duree_minutes,
    ]);

    /* --- Débit des crédits --- */
    $stmt_credit = $conn->prepare('UPDATE users SET credits = credits - 2 WHERE id = ?');
    $stmt_credit->execute([$user_id]);

    $conn->commit();

    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet publié avec succès !'));
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // En prod: log $e->getMessage()
    header('Location: /views/ajouter_trajet.php?message=' . rawurlencode('Erreur serveur, veuillez réessayer.'));
    exit;
}
