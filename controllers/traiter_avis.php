<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Accès employé uniquement
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employe') {
    header("Location: /controllers/connexion.php");
    exit();
}

// Vérif CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_avis']) || !hash_equals($_SESSION['csrf_avis'], $_POST['csrf_token'])) {
    header("Location: /views/avis_attente.php?message=" . rawurlencode("Session expirée, veuillez réessayer."));
    exit();
}

$avis_id = isset($_POST['avis_id']) ? (int)$_POST['avis_id'] : 0;
$action = $_POST['action'] ?? '';

if ($avis_id <= 0 || !in_array($action, ['valider', 'rejeter'], true)) {
    header("Location: /views/avis_attente.php?message=" . rawurlencode("Paramètres invalides."));
    exit();
}

// Traitement
try {
    if ($action === 'valider') {
        $stmt = $conn->prepare("UPDATE avis SET statut = 'valide' WHERE id = ?");
        $stmt->execute([$avis_id]);
        $msg = "✅ Avis validé.";
    } elseif ($action === 'rejeter') {
        $stmt = $conn->prepare("UPDATE avis SET statut = 'rejete' WHERE id = ?");
        $stmt->execute([$avis_id]);
        $msg = "🚫 Avis rejeté.";
    }

    header("Location: /views/avis_attente.php?message=" . rawurlencode($msg));
    exit();
} catch (Throwable $e) {
    header("Location: /views/avis_attente.php?message=" . rawurlencode("Erreur serveur."));
    exit();
}
