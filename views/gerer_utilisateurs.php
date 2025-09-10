<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// --- Accès admin uniquement ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /controllers/connexion.php?message=' . urlencode('Accès réservé aux administrateurs.'));
    exit();
}

// --- Traitement des actions ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action  = $_POST['action']   ?? '';
    $userId  = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $newRole = $_POST['new_role'] ?? '';

    // Liste blanche : doit coller à tes autres pages
    $rolesAllowed = ['utilisateur', 'employe', 'admin', 'utilisateur suspendu'];

    if ($userId > 0) {
        if ($action === 'changer_role' && in_array($newRole, $rolesAllowed, true)) {
            // Optionnel : empêcher un admin de se retirer lui-même ses droits
            if ($userId === (int)$_SESSION['user_id'] && $newRole !== 'admin') {
                header('Location: /views/gerer_utilisateurs.php?message=' . urlencode("Action refusée : vous ne pouvez pas vous retirer vos droits."));
                exit();
            }
            $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->execute([$newRole, $userId]);
            header('Location: /views/gerer_utilisateurs.php?message=' . urlencode('Rôle mis à jour.'));
            exit();

        } elseif ($action === 'suspendre') {
            $stmt = $conn->prepare("UPDATE users SET role = 'utilisateur suspendu' WHERE id = ?");
            $stmt->execute([$userId]);
            header('Location: /views/gerer_utilisateurs.php?message=' . urlencode('Utilisateur suspendu.'));
            exit();
        }
    }

    header('Location: /views/gerer_utilisateurs.php?message=' . urlencode('Requête invalide.'));
    exit();
}

// --- Listes ---
$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'utilisateur' ORDER BY nom ASC");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'utilisateur suspendu' ORDER BY nom ASC");
$suspendus = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-5">
    <h2>👥 Gestion des utilisateurs</h2>

    <?php if (!empty($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (empty($utilisateurs)): ?>
        <p class="text-muted">Aucun utilisateur trouvé.</p>
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
                <?php foreach ($utilisateurs as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <!-- Changer de rôle -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                <select name="new_role" class="form-select d-inline w-auto">
                                    <option value="" selected disabled>— choisir —</option>
                                    <option value="utilisateur">Utilisateur</option>
                                    <option value="employe">Employé</option>
                                    <option value="admin">Admin</option>
                                    <option value="utilisateur suspendu">Utilisateur suspendu</option>
                                </select>
                                <button type="submit" name="action" value="changer_role" class="btn btn-primary btn-sm">
                                    Changer rôle
                                </button>
                            </form>

                            <!-- Suspendre -->
                            <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Confirmer la suspension ?');">
                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
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
    <h2>🚫 Utilisateurs suspendus</h2>

    <?php if (empty($suspendus)): ?>
        <p class="text-muted">Aucun utilisateur suspendu.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th style="width:300px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suspendus as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <!-- Réactiver (repasse en 'utilisateur') -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                                <input type="hidden" name="new_role" value="utilisateur">
                                <button type="submit" name="action" value="changer_role" class="btn btn-success btn-sm">
                                    ✅ Réactiver
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
