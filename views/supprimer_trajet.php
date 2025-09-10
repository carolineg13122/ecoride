<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Connectez-vous pour annuler un trajet.'));
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$trajet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($trajet_id <= 0) {
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet non spécifié.'));
    exit;
}

try {
    // Vérifier que le trajet appartient bien au user et récupérer son statut/prix
    $stmt = $conn->prepare('SELECT id, user_id, statut, depart, destination, date, prix FROM trajets WHERE id = ? AND user_id = ?');
    $stmt->execute([$trajet_id, $user_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        header('Location: /views/mes_trajets.php?message=' . rawurlencode("Trajet introuvable ou non autorisé."));
        exit;
    }

    // Règle métier : on n’annule pas un trajet déjà terminé (adapter si besoin)
    if (($trajet['statut'] ?? '') === 'termine') {
        header('Location: /views/mes_trajets.php?message=' . rawurlencode('Un trajet terminé ne peut plus être annulé.'));
        exit;
    }

    // Récupérer les passagers avant modif (pour remboursement & mails)
    $stmt = $conn->prepare("
        SELECT r.user_id, u.email, u.prenom
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        WHERE r.trajet_id = ?
    ");
    $stmt->execute([$trajet_id]);
    $passagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn->beginTransaction();

    // Rembourser les crédits des passagers
    if (!empty($passagers)) {
        $stmt_credit = $conn->prepare('UPDATE users SET credits = credits + :montant WHERE id = :uid');
        foreach ($passagers as $p) {
            $stmt_credit->execute([
                ':montant' => (float)$trajet['prix'],
                ':uid'     => (int)$p['user_id'],
            ]);
        }
    }

    // Supprimer les réservations liées
    $stmt = $conn->prepare('DELETE FROM reservations WHERE trajet_id = ?');
    $stmt->execute([$trajet_id]);

    // Supprimer le trajet
    $stmt = $conn->prepare('DELETE FROM trajets WHERE id = ? AND user_id = ?');
    $stmt->execute([$trajet_id, $user_id]);

    $conn->commit();

    // Notifications email (simple mail() ; si tu veux PHPMailer plus tard, on l’ajoutera)
    if (!empty($passagers)) {
        $subject  = "🚫 Annulation de votre covoiturage";
        $headers  = "From: noreply@ecoride.com\r\n";
        foreach ($passagers as $p) {
            $prenom = (string)($p['prenom'] ?? '');
            $to     = (string)($p['email']  ?? '');
            if ($to !== '') {
                $message  = "Bonjour {$prenom},\n\n";
                $message .= "Le trajet de " . ($trajet['depart'] ?? '') . " à " . ($trajet['destination'] ?? '') .
                            " prévu le " . ($trajet['date'] ?? '') . " a été annulé par le chauffeur.\n";
                $message .= "Vos crédits ont été automatiquement remboursés.\n\n";
                $message .= "Merci pour votre compréhension,\nL'équipe EcoRide";
                @mail($to, $subject, $message, $headers);
            }
        }
    }

    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet annulé avec succès. Les passagers ont été remboursés et informés.'));
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // En production, logger l'erreur : $e->getMessage()
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Erreur serveur lors de l’annulation.'));
    exit;
}
