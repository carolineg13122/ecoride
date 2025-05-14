<?php
session_start();
require_once("config/database.php");

// Vérifier que seul l'admin y accède
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php?message=Accès réservé aux administrateurs.");
    exit();
}

// Récupérer les données pour les graphiques

// Nombre de covoiturages par jour
$stmt = $conn->query("
    SELECT DATE(date) as jour, COUNT(*) as nb_trajets
    FROM trajets
    GROUP BY jour
    ORDER BY jour ASC
");
$trajets_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crédits gagnés par jour
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
    <h2>📈 Statistiques</h2>

    <canvas id="trajetsChart" class="my-4"></canvas>
    <canvas id="creditsChart" class="my-4"></canvas>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Données pour les trajets
    const labelsTrajets = <?= json_encode(array_column($trajets_par_jour, 'jour')) ?>;
    const dataTrajets = <?= json_encode(array_column($trajets_par_jour, 'nb_trajets')) ?>;

    // Données pour les crédits
    const labelsCredits = <?= json_encode(array_column($credits_par_jour, 'jour')) ?>;
    const dataCredits = <?= json_encode(array_column($credits_par_jour, 'credits_gagnes')) ?>;

    // Graphique trajets
    new Chart(document.getElementById('trajetsChart'), {
        type: 'line',
        data: {
            labels: labelsTrajets,
            datasets: [{
                label: 'Nombre de covoiturages par jour',
                data: dataTrajets,
                borderColor: 'blue',
                backgroundColor: 'lightblue'
            }]
        }
    });

    // Graphique crédits
    new Chart(document.getElementById('creditsChart'), {
        type: 'bar',
        data: {
            labels: labelsCredits,
            datasets: [{
                label: 'Crédits gagnés par jour',
                data: dataCredits,
                borderColor: 'green',
                backgroundColor: 'lightgreen'
            }]
        }
    });
</script>

<?php require_once("templates/footer.php"); ?>
