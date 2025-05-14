<?php
session_start();
require_once("config/database.php");

// Vérification : seul un admin peut accéder
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php?message=Accès réservé aux administrateurs.");
    exit();
}
// Traitement du changement de rôle ou suspension
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $new_role = $_POST['new_role'] ?? null;

    if ($action === 'changer_role' && $user_id && $new_role) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
    } elseif ($action === 'suspendre' && $user_id) {
        $stmt = $conn->prepare("UPDATE users SET role = 'suspendu' WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    header("Location: gerer_employes.php?message=Employé suspendu");
    exit();
}

// Récupérer uniquement les employés
$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'employe'");
$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>🧑‍💼 Gestion des employés</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <?php if (count($employes) === 0): ?>
        <p class="text-muted">Aucun employé trouvé.</p>
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
                <?php foreach ($employes as $employe): ?>
                    <tr>
                    <td><?= htmlspecialchars($employe['nom']) ?></td>
                        <td><?= htmlspecialchars($employe['email']) ?></td>
                        <td><?= htmlspecialchars($employe['role']) ?></td>
                        <td>
                            <!-- Changer de rôle -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $employe['id'] ?>">
                                <select name="new_role" class="form-select d-inline w-auto">
                                    <option value="aucun"> </option>
                                    <option value="utilisateur">Utilisateur</option>
                                    <option value="employe">Employé</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button type="submit" name="action" value="changer_role" class="btn btn-primary btn-sm">Changer rôle</button>
                            </form>

                            <!-- Suspendre le compte -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $employe['id'] ?>">
                                <button type="submit" name="action" value="suspendre" class="btn btn-danger btn-sm" onclick="return confirm('Confirmer la suspension ?')">Suspendre</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
   <?php endif; ?>
</div>
<?php
// Récupérer uniquement les employés
$stmt = $conn->query("SELECT id, nom, email, role FROM users WHERE role = 'employé suspendu'");
$suspendus2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-5">
    <h2>🧑‍💼 Gestion des employés suspendus</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <?php if (count($suspendus2) === 0): ?>
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
                <?php foreach ($suspendus2 as $suspendu): ?>
                    <tr>
                        <td><?= htmlspecialchars($suspendu['nom']) ?></td>
                        <td><?= htmlspecialchars($suspendu['email']) ?></td>
                        <td><?= htmlspecialchars($suspendu['role']) ?></td>
                        <td>
                            <!-- Changer de rôle -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $suspendu['id'] ?>">
                                <select name="new_role" class="form-select d-inline w-auto">
                                    <option value="aucun"></option>
                                    <option value="utilisateur">Utilisateur</option>
                                    <option value="employe">Employé</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button type="submit" name="action" value="changer_role" class="btn btn-primary btn-sm">Changer rôle</button>
                            </form>

                            
                        </td>
                    </tr>
                <?php endforeach; ?>
                
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require_once("templates/footer.php"); ?>
