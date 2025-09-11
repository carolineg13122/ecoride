<?php
session_start();
require_once('../config/database.php');
require_once('../models/Trajet.php');

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupération des trajets
$stmt = $conn->prepare("SELECT * FROM trajets WHERE user_id = ?");
$stmt->execute([$user_id]);

$trajets = [];
while ($row = $stmt->fetch()) {
    $trajets[] = new Trajet($row);
}

// Inclusion de la vue
include('../views/mes_trajets.php');
