<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php');
    exit;
}

$user_id   = (int)($_SESSION['user_id']);
$trajet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action    = $_GET['action'] ?? '';

if ($trajet_id <= 0 || !in_array($action, ['demarrer', 'terminer'], true)) {
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Paramètres manquants ou action invalide.'));
    exit;
}

try {
    // Vérifier que le trajet appartient bien à l'utilisateur
    $stmt = $conn->prepare('SELECT id, user_id, statut FROM trajets WHERE id = ? AND user_id = ?');
    $stmt->execute([$trajet_id, $user_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet introuvable ou non autorisé.'));
        exit;
    }

    $statut_actuel = $trajet['statut'] ?? '';

    if ($action === 'demarrer') {
        // Transition autorisée: à_venir -> en_cours
        if ($statut_actuel !== 'à_venir') {
            header('Location: /views/mes_trajets.php?message=' . rawurlencode("Ce trajet ne peut pas être démarré (statut actuel: $statut_actuel)."));
            exit;
        }

        $stmt = $conn->prepare('UPDATE trajets SET statut = :s WHERE id = :id');
        $stmt->execute([':s' => 'en_cours', ':id' => $trajet_id]);

        header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet démarré !'));
        exit;

    } elseif ($action === 'terminer') {
        // Transition autorisée: en_cours -> termine
        if ($statut_actuel !== 'en_cours') {
            header('Location: /views/mes_trajets.php?message=' . rawurlencode("Ce trajet ne peut pas être terminé (statut actuel: $statut_actuel)."));
            exit;
        }

        // Marquer le trajet terminé
        $stmt = $conn->prepare('UPDATE trajets SET statut = :s WHERE id = :id');
        $stmt->execute([':s' => 'termine', ':id' => $trajet_id]);

        // Notifier les passagers pour confirmation/avis
        $stmt = $conn->prepare("
            SELECT u.email, u.prenom
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            WHERE r.trajet_id = ?
        ");
        $stmt->execute([$trajet_id]);
        $passagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($passagers)) {
            $subject = "📝 Confirmation de votre covoiturage EcoRide";
            $template = "Bonjour %prenom%,\n\n"
                      . "Le trajet auquel vous avez participé est maintenant terminé.\n"
                      . "Merci de vous rendre sur votre espace EcoRide pour confirmer que tout s'est bien passé,\n"
                      . "laisser une note ou signaler un éventuel problème.\n\n"
                      . "— L'équipe EcoRide";
            // En-têtes simples (adapter si besoin)
            $headers = "From: noreply@ecoride.com\r\n";

            foreach ($passagers as $p) {
                $to   = $p['email'] ?? '';
                $body = str_replace('%prenom%', (string)$p['prenom'], $template);
                if ($to !== '') {
                    @mail($to, $subject, $body, $headers); // on ignore les erreurs d'envoi ici
                }
            }
        }

        header('Location: /views/mes_trajets.php?message=' . rawurlencode('Trajet terminé. Les passagers ont été notifiés.'));
        exit;
    }

    // Fallback
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Action inconnue.'));
    exit;

} catch (Throwable $e) {
    // En production : logger $e->getMessage()
    header('Location: /views/mes_trajets.php?message=' . rawurlencode('Erreur serveur.'));
    exit;
}
