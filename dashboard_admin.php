<?php
session_start();
require_once("config/database.php");

// Vérification : seulement admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php?message=Accès réservé aux administrateurs.");
    exit();
}

// Récupérer les statistiques principales
$total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'utilisateur'")->fetchColumn();
$total_users2 = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'utilisateur suspendu'")->fetchColumn();
$total_employes = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employe'")->fetchColumn();
$total_employes2 = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employé suspendu'")->fetchColumn();
$total_trajets = $conn->query("SELECT COUNT(*) FROM trajets")->fetchColumn();
$total_credits = $conn->query("SELECT SUM(2) FROM trajets")->fetchColumn();

// Récupérer données pour les graphiques
$stmt = $conn->query("
    SELECT DATE(date) as jour, COUNT(*) as nb_trajets
    FROM trajets
    GROUP BY jour
    ORDER BY jour ASC
");
$trajets_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("
    SELECT DATE(date) as jour, SUM(2) as credits_gagnes
    FROM trajets
    GROUP BY jour
    ORDER BY jour ASC
");
$credits_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once("templates/header.php"); ?>

<div class="container mt-5">
    <h2>👑 Dashboard Administrateur</h2>

    <!-- Cartes Statistiques -->
    <div class="row text-center my-3">
        <div class="col-md-3 mb-3">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">👥 Utilisateurs</h5>
                    <p class="card-text"><?= $total_users ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">🧑‍💼 Employés</h5>
                    <p class="card-text"><?= $total_employes ?></p>
                </div>
            </div>
        </div>
            <div class="col-md-3 mb-3">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">👥 Utilisateurs suspendus</h5>
                    <p class="card-text"><?= $total_users2?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">🧑‍💼 Employés suspendus</h5>
                    <p class="card-text"><?= $total_employes2 ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">🚗 Trajets</h5>
                    <p class="card-text"><?= $total_trajets ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">💳 Crédits Gagnés</h5>
                    <p class="card-text"><?= $total_credits ?> crédits</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row mt-5">
        <div class="col-md-6">
            <h4 class="text-center">📈 Trajets par Jour</h4>
            <canvas id="trajetsChart"></canvas>
        </div>
        <div class="col-md-6">
            <h4 class="text-center">💵 Crédits par Jour</h4>
            <canvas id="creditsChart"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const labelsTrajets = <?= json_encode(array_column($trajets_par_jour, 'jour')) ?>;
    const dataTrajets = <?= json_encode(array_column($trajets_par_jour, 'nb_trajets')) ?>;

    const labelsCredits = <?= json_encode(array_column($credits_par_jour, 'jour')) ?>;
    const dataCredits = <?= json_encode(array_column($credits_par_jour, 'credits_gagnes')) ?>;

    new Chart(document.getElementById('trajetsChart'), {
        type: 'line',
        data: {
            labels: labelsTrajets,
            datasets: [{
                label: 'Trajets / Jour',
                data: dataTrajets,
                borderColor: 'blue',
                backgroundColor: 'lightblue'
            }]
        }
    });

    new Chart(document.getElementById('creditsChart'), {
        type: 'bar',
        data: {
            labels: labelsCredits,
            datasets: [{
                label: 'Crédits gagnés / Jour',
                data: dataCredits,
                backgroundColor: 'lightgreen',
                borderColor: 'green'
            }]
        }
    });
</script>

<?php require_once("templates/footer.php"); ?>
