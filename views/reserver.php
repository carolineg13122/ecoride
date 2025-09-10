<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /controllers/connexion.php?message=' . urlencode('Veuillez vous connecter pour réserver un trajet.'));
    exit();
}

$user_id   = (int) $_SESSION['user_id'];
$trajet_id = isset($_POST['trajet_id']) ? (int) $_POST['trajet_id'] : 0;

if ($trajet_id <= 0) {
    header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Trajet invalide.'));
    exit();
}

try {
    $conn->beginTransaction();

    // Verrouillage de la ligne trajet pendant la réservation
    $stmt = $conn->prepare("SELECT id, user_id AS chauffeur_id, statut, places, prix, date 
                            FROM trajets WHERE id = ? FOR UPDATE");
    $stmt->execute([$trajet_id]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode("Ce trajet n'existe pas."));
        exit();
    }

    if ((int)$trajet['chauffeur_id'] === $user_id) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Vous ne pouvez pas réserver votre propre trajet.'));
        exit();
    }

    if (($trajet['statut'] ?? '') !== 'à_venir') {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Ce trajet n’est plus réservable.'));
        exit();
    }

    if ((int)$trajet['places'] <= 0) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Plus de places disponibles pour ce trajet.'));
        exit();
    }

    // Déjà réservé ?
    $stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND trajet_id = ?");
    $stmt->execute([$user_id, $trajet_id]);
    if ((int)$stmt->fetchColumn() > 0) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Vous avez déjà réservé ce trajet.'));
        exit();
    }

    // Crédits suffisants ?
    $stmt = $conn->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $credits = (int)$stmt->fetchColumn();
    if ($credits < 2) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode("Crédits insuffisants (2 requis)."));
        exit();
    }

    // 1) Réservation
    $stmt = $conn->prepare("INSERT INTO reservations (user_id, trajet_id, date_reservation) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $trajet_id]);

    // 2) –1 place (sécurisé)
    $stmt = $conn->prepare("UPDATE trajets SET places = places - 1 WHERE id = ? AND places > 0");
    $stmt->execute([$trajet_id]);
    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Plus de places disponibles.'));
        exit();
    }

    // 3) –2 crédits (sécurisé)
    $stmt = $conn->prepare("UPDATE users SET credits = credits - 2 WHERE id = ? AND credits >= 2");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Crédits insuffisants.'));
        exit();
    }

    // Infos chauffeur pour mail
    $stmt = $conn->prepare("SELECT u.email, u.prenom, u.nom
                            FROM trajets t JOIN users u ON t.user_id = u.id
                            WHERE t.id = ?");
    $stmt->execute([$trajet_id]);
    $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

    $conn->commit();

    // Mail (best effort)
    $info = "Réservation enregistrée ✅";
    if ($chauffeur && !empty($chauffeur['email'])) {
        $to      = $chauffeur['email'];
        $subject = "🚗 Nouvelle réservation sur EcoRide";
        $message = "Bonjour {$chauffeur['prenom']} {$chauffeur['nom']}," . "\n\n"
                 . "Un passager vient de réserver une place pour votre trajet #{$trajet_id}." . "\n"
                 . "Connectez-vous à votre compte pour consulter les détails." . "\n\n"
                 . "Merci d'utiliser EcoRide 🚀";
        $headers = "From: contact@ecoride.fr\r\nContent-Type: text/plain; charset=utf-8";

        if (!@mail($to, $subject, $message, $headers)) {
            $info .= " (e-mail non envoyé en local)";
        } else {
            $info .= " (e-mail envoyé au chauffeur)";
        }
    } else {
        $info .= " (aucun e-mail chauffeur)";
    }

    header('Location: /views/mes_reservations.php?message=' . urlencode($info));
    exit();

} catch (Throwable $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    header('Location: /views/details.php?id=' . $trajet_id . '&erreur=' . urlencode('Erreur lors de la réservation : ' . $e->getMessage()));
    exit();
}
