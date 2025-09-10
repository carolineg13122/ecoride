<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Accès admin uniquement
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /controllers/connexion.php?message=' . urlencode('Accès réservé aux administrateurs.'));
    exit();
}

// --- Traitement actions ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action   = $_POST['action']   ?? '';
    $userId   = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $newRole  = $_POST['new_role'] ?? '';

    // Liste blanche des rôles autorisés (doit coller à ton schéma)
    $rolesAllowed = ['utilisateur', 'employe', 'admin', 'employé suspendu'];

    // Sécurité minimale
    if ($userId > 0) {
        if ($action === 'changer_role' && in_array($newRole, $rolesAllowed, true)) {
            // Empêcher un admin de se retirer lui-même le rôle admin (optionnel)
            if ($userId === (int)$_SESSION['user_id'] && $newRole !== 'admin') {
                header('Location: /views/gerer_employes.php?message=' . urlencode("Action refusée : vous ne pouvez pas vous retirer vos droits."));
                exit();
            }
            $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->execute([$newRole, $userId]);
            header('Location: /views/gerer_employes.php?message=' . urlencode('Rôle mis à jour.'));
            exit();

        } elseif ($action === 'suspendre') {
            // Cohérence avec tes autres pages : “employé suspendu”
            $stmt = $conn->prepare("UPDATE users SET role = 'employé suspendu' WHERE id = ?");
            $stmt->execute([$userId]);
            header('Location: /views/gerer_employes.php?message=' . urlencode('Employé suspendu.'));
            exit();
        }
    }

    header('Location: /views/gerer_employes.php?message=' . urlencode('Requête invalide.'));
    exit();
}

// --- Récupération des listes ---
$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'employe' ORDER BY nom ASC");
$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'employé suspendu' ORDER BY nom ASC");
$suspendus = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-5">
    <h2>🧑‍💼 Gestion des employés</h2>

    <?php if (!empty($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (empty($employes)): ?>
        <p class="text-muted">Aucun employé trouvé.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th style="width:420px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employes as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($e['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($e['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <!-- Changer de rôle -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= (int)$e['id'] ?>">
                            <select name="new_role" class="form-select d-inline w-auto">
                                <option value="" selected disabled>— choisir —</option>
                                <option value="utilisateur">Utilisateur</option>
                                <option value="employe">Employé</option>
                                <option value="admin">Admin</option>
                                <option value="employé suspendu">Employé suspendu</option>
                            </select>
                            <button type="submit" name="action" value="changer_role" class="btn btn-primary btn-sm">
                                Changer rôle
                            </button>
                        </form>

                        <!-- Suspendre -->
                        <form method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suspension ?');">
                            <input type="hidden" name="user_id" value="<?= (int)$e['id'] ?>">
                            <button type="submit" name="action" value="suspendre" class="btn btn-danger btn-sm">
                                Suspendre
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="container mt-5">
    <h2>🧑‍💼 Employés suspendus</h2>

    <?php if (empty($suspendus)): ?>
        <p class="text-muted">Aucun employé suspendu.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th style="width:360px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($suspendus as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($s['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($s['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <!-- Réactiver / changer rôle -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                            <select name="new_role" class="form-select d-inline w-auto">
                                <option value="" selected disabled>— choisir —</option>
                                <option value="utilisateur">Utilisateur</option>
                                <option value="employe">Employé</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button type="submit" name="action" value="changer_role" class="btn btn-primary btn-sm">
                                Changer rôle
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
