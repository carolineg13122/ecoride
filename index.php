<?php 
require_once("templates/header.php");
require_once("config/database.php");
?>

<section class="hero text-center">
  <div class="container">
    <h1>Bienvenue sur EcoRide</h1>
    <p>La plateforme de covoiturage écologique</p>
    <img src="assets/images/road.jpg" alt="Présentation EcoRide" class="img-fluid my-4">

    <!-- 🔍 Formulaire de recherche -->
    <form id="search-form" class="search-form form-inline justify-content-center">
      <div class="form-group mb-2">
        <input type="text" class="form-control" id="depart" placeholder="Adresse de départ" required>
      </div>
      <div class="form-group mx-sm-3 mb-2">
        <input type="text" class="form-control" id="destination" placeholder="Adresse d'arrivée" required>
      </div>
      <div class="form-group mb-2">
        <input type="date" class="form-control" id="date" required>
      </div>
      <button type="submit" class="btn btn-primary mb-2 ml-2">Rechercher</button>
    </form>
  </div>

  <!-- 🎯 Résultats -->
  <div class="container mt-4" id="resultats">
    <h3>Résultats des covoiturages</h3>
    <div id="liste-covoiturages"></div>
  </div>
</section>
<div class="container mt-4" id="filtres">
  <h4>Affiner votre recherche</h4>
  <div class="row">
    <!-- 🌿 Écologique -->
    <div class="col-md-3">
      <label for="filtre-eco">Voyage écologique</label>
      <select id="filtre-eco" class="form-control">
        <option value="">Tous</option>
        <option value="1">Oui</option>
        <option value="0">Non</option>
      </select>
    </div>

    <!-- 💶 Prix max -->
    <div class="col-md-3">
      <label for="filtre-prix">Prix maximum (€)</label>
      <input type="number" id="filtre-prix" class="form-control" placeholder="ex: 30">
    </div>
    <!-- ⏱️ Durée maximale -->
    <div class="col-md-3">
      <label for="filtre-duree">Durée max (en minutes)</label>
      <input type="number" id="filtre-duree" class="form-control" placeholder="ex: 120">
    </div>

    <!-- ⭐ Note min -->
    <div class="col-md-3">
      <label for="filtre-note">Note minimum du chauffeur</label>
      <select id="filtre-note" class="form-control">
        <option value="">Toutes</option>
        <option value="3">3⭐ et +</option>
        <option value="4">4⭐ et +</option>
        <option value="5">5⭐</option>
      </select>
    </div>

    <div class="col-md-3 d-flex align-items-end">
      <button id="appliquer-filtres" class="btn btn-secondary">Appliquer</button>
    </div>
  </div>
</div>

<!-- 🧾 À propos -->
<section class="container my-5 about">
  <div class="row">
    <div class="col-md-6">
      <h2>À propos d'EcoRide</h2>
      <p>
        EcoRide a été créé pour réduire l'impact environnemental des déplacements en encourageant le covoiturage.
        Découvrez une solution économique et écologique pour vos trajets.
      </p>
    </div>
  </div>
</section>

<?php require_once("templates/footer.php"); ?>

<!-- 📜 Script JS AJAX -->
<script src="assets/js/script.js"></script>