<?php
session_start();
require_once("config/database.php");

// Vérification : seul un admin peut accéder
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php?message=Accès réservé aux administrateurs.");
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $new_role = $_POST['new_role'] ?? null;

    if ($action === 'changer_role' && $user_id && $new_role && $new_role !== 'aucun') {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
    } elseif ($action === 'suspendre' && $user_id) {
        $stmt = $conn->prepare("UPDATE users SET role = 'utilisateur suspendu' WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    header("Location: gerer_utilisateurs.php?message=Action effectuée");
    exit();
}

// Liste des utilisateurs actifs
$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'utilisateur' ORDER BY role ASC, nom ASC");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des utilisateurs suspendus
$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'utilisateur suspendu'");
$suspendus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>👥 Gestion des utilisateurs</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <?php if (count($utilisateurs) === 0): ?>
        <p class="text-muted">Aucun utilisateur trouvé.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['nom']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <!-- Changer de rôle -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="new_role" class="form-select d-inline w-auto">
                                    <option value="aucun"></option>
                                    <option value="utilisateur">Utilisateur</option>
                                    <option value="employe">Employé</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button type="submit" name="action" value="changer_role" class="btn btn-primary btn-sm">Changer rôle</button>
                            </form>

                            <!-- Suspendre le compte -->
                            <form method="POST" class="d-inline ms-2">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="action" value="suspendre" class="btn btn-danger btn-sm" onclick="return confirm('Confirmer la suspension ?')">Suspendre</button>
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

    <?php if (count($suspendus) === 0): ?>
        <p class="text-muted">Aucun utilisateur suspendu.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suspendus as $suspendu): ?>
                    <tr>
                        <td><?= htmlspecialchars($suspendu['nom']) ?></td>
                        <td><?= htmlspecialchars($suspendu['email']) ?></td>
                        <td><?= htmlspecialchars($suspendu['role']) ?></td>
                        <td>
                            <!-- Réactiver -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $suspendu['id'] ?>">
                                <input type="hidden" name="new_role" value="utilisateur">
                                <button type="submit" name="action" value="changer_role" class="btn btn-success btn-sm">✅ Réactiver</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once("templates/footer.php"); ?>
