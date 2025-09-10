<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Accès admin uniquement
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /controllers/connexion.php?message=' . rawurlencode('Accès réservé aux administrateurs.'));
    exit;
}

/* -----------------------------
   Requêtes pour les graphiques
   ----------------------------- */

// NB: on force DATE(t.date) pour agréger par jour proprement.
$stmt = $conn->query("
    SELECT DATE(date) AS jour, COUNT(*) AS nb_trajets
    FROM trajets
    GROUP BY DATE(date)
    ORDER BY jour ASC
");
$trajets_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crédits « gagnés » par jour (2 par trajet publié, comme dans ton dashboard)
$stmt = $conn->query("
    SELECT DATE(date) AS jour, COUNT(*) * 2 AS credits_gagnes
    FROM trajets
    GROUP BY DATE(date)
    ORDER BY jour ASC
");
$credits_par_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prépare les tableaux JS (évite les notices si vide)
$labelsTrajets = array_column($trajets_par_jour, 'jour');
$dataTrajets   = array_map('intval', array_column($trajets_par_jour, 'nb_trajets'));

$labelsCredits = array_column($credits_par_jour, 'jour');
$dataCredits   = array_map('intval', array_column($credits_par_jour, 'credits_gagnes'));

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container mt-5">
    <h2>📈 Statistiques</h2>

    <canvas id="trajetsChart" class="my-4"></canvas>
    <canvas id="creditsChart" class="my-4"></canvas>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données PHP -> JS (encodage sûr)
const labelsTrajets = <?= json_encode($labelsTrajets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const dataTrajets   = <?= json_encode($dataTrajets,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const labelsCredits = <?= json_encode($labelsCredits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const dataCredits   = <?= json_encode($dataCredits,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

// Graphique trajets
new Chart(document.getElementById('trajetsChart'), {
  type: 'line',
  data: {
    labels: labelsTrajets,
    datasets: [{
      label: 'Nombre de covoiturages par jour',
      data: dataTrajets,
      borderColor: 'blue',
      backgroundColor: 'lightblue',
      tension: 0.25
    }]
  },
  options: {
    plugins: { legend: { display: true } },
    scales: {
      x: { ticks: { autoSkip: true, maxTicksLimit: 10 } },
      y: { beginAtZero: true, precision: 0 }
    }
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
  },
  options: {
    plugins: { legend: { display: true } },
    scales: {
      x: { ticks: { autoSkip: true, maxTicksLimit: 10 } },
      y: { beginAtZero: true, precision: 0 }
    }
  }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
