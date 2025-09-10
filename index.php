<?php 
require_once("templates/header.php");
require_once("config/database.php");
?>

<!-- HERO + RECHERCHE -->
<section class="hero text-center py-5 bg-light">
  <div class="container">
    <h1 class="mb-2">Bienvenue sur EcoRide</h1>
    <p class="lead mb-4">La plateforme de covoiturage écologique</p>
    <img src="assets/images/road.jpg" alt="Présentation EcoRide" class="img-fluid my-4 rounded shadow">

    <!-- 🔍 Formulaire de recherche -->
    <form id="search-form" class="mt-3" method="GET">
      <div class="row g-2 justify-content-center">
        <div class="col-12 col-sm-6 col-md-3">
          <input type="text" class="form-control" id="depart" name="depart"
                placeholder="Adresse de départ" required>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <input type="text" class="form-control" id="destination" name="destination"
                placeholder="Adresse d'arrivée" required>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <input type="date" class="form-control" id="date" name="date" required>
        </div>
        <div class="col-12 col-sm-6 col-md-2 d-grid">
          <button type="submit" class="btn btn-primary">Rechercher</button>
        </div>
      </div>
    </form>


  <!-- 🎯 Résultats -->
  <div class="container mt-5" id="resultats">
    <h3 class="text-center mb-4">Résultats des covoiturages</h3>
    <div id="liste-covoiturages" class="mx-auto" style="max-width: 720px;"></div>
  </div>
</section>


<!-- FILTRES -->
<div class="container mt-5" id="filtres">
  <h4 class="text-center mb-3">Affiner votre recherche</h4>
  <div class="row g-3 justify-content-center">

    <!-- 🌿 Écologique -->
    <div class="col-12 col-md-3">
      <label for="filtre-eco" class="form-label">Voyage écologique</label>
      <select id="filtre-eco" class="form-control">
        <option value="">Tous</option>
        <option value="1">Oui</option>
        <option value="0">Non</option>
      </select>
    </div>

    <!-- 💶 Prix max -->
    <div class="col-12 col-md-3">
      <label for="filtre-prix" class="form-label">Prix maximum (€)</label>
      <input type="number" id="filtre-prix" class="form-control" placeholder="ex: 30">
    </div>

    <!-- ⏱️ Durée max -->
    <div class="col-12 col-md-3">
      <label for="filtre-duree" class="form-label">Durée max (en minutes)</label>
      <input type="number" id="filtre-duree" class="form-control" placeholder="ex: 120">
    </div>

    <!-- ⭐ Note min -->
    <div class="col-12 col-md-3">
      <label for="filtre-note" class="form-label">Note minimum du chauffeur</label>
      <select id="filtre-note" class="form-control">
        <option value="">Toutes</option>
        <option value="3">3⭐ et +</option>
        <option value="4">4⭐ et +</option>
        <option value="5">5⭐</option>
      </select>
    </div>

    <!-- Bouton appliquer -->
    <div class="col-12 col-md-2 d-flex align-items-end">
      <button id="appliquer-filtres" class="btn btn-secondary w-100">Appliquer</button>
    </div>
  </div>
</div>

<!-- 🧾 À propos -->
<section class="container my-5 about">
  <div class="row justify-content-center text-center">
    <div class="col-md-8">
      <h2 class="mb-3">À propos d'EcoRide</h2>
      <p class="lead">
        EcoRide a été créé pour réduire l'impact environnemental des déplacements en encourageant le covoiturage.
        Découvrez une solution économique et écologique pour vos trajets.
      </p>
    </div>
  </div>
</section>

<?php require_once("templates/footer.php"); ?>

<!-- 📜 Script JS AJAX -->
<script src="assets/js/script.js?v=3"></script>
