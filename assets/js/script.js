// ===========================
// Helpers
// ===========================

// Affiche les étoiles selon la note (nombre ou "N/A")
function renderStars(note) {
  if (note === null || note === undefined || note === 'N/A' || note === '') return 'N/A';
  const n = Number(note);
  if (Number.isNaN(n)) return 'N/A';
  const full = Math.floor(n);
  const half = (n - full) >= 0.5 ? 1 : 0;
  const empty = Math.max(0, 5 - full - half);
  return '⭐'.repeat(full) + (half ? '✨' : '') + '☆'.repeat(empty);
}

// Construit une URL absolue vers un chemin relatif (marche à la racine ou sous-dossier)
function absoluteUrl(relPathWithQuery = '') {
  // répertoire de la page courante (ex: "/" ou "/ecoride/")
  const currentDir = window.location.pathname.endsWith('/')
    ? window.location.pathname
    : window.location.pathname.replace(/[^/]+$/, ''); // enlève "index.php" éventuel
  return new URL(relPathWithQuery, window.location.origin + currentDir).toString();
}

// Récupère une valeur d'input (retourne "")
function val(id) {
  const el = document.getElementById(id);
  return (el && el.value) ? el.value : '';
}

// ===========================
// Recherche AJAX
// ===========================

function rechercherTrajets() {
  const depart      = val('depart');
  const destination = val('destination');
  const date        = val('date');

  const filtreEco   = val('filtre-eco');
  const filtrePrix  = val('filtre-prix');
  const filtreDuree = val('filtre-duree');
  const filtreNote  = (document.getElementById('filtre-note')?.value) || '';

  const params = new URLSearchParams({
    depart, destination, date, filtreEco, filtrePrix, filtreDuree, filtreNote
  });

  // >>> IMPORTANT : endpoint PHP placé dans /views
  const url = absoluteUrl(`views/rechercher.php?${params.toString()}`);

  const container = document.getElementById('liste-covoiturages');
  container.innerHTML = '<div class="text-muted">🔎 Recherche en cours…</div>';

  fetch(url)
    .then(async (res) => {
      const text = await res.text(); // lit brut pour détecter les erreurs HTML/PHP
      let data;
      try { data = JSON.parse(text); }
      catch { throw new Error(`Réponse non JSON (${res.status}) : ${text.slice(0,200)}`); }
      if (!res.ok) throw new Error(data.error || `Erreur ${res.status}`);
      return data;
    })
    .then((data) => {
      container.innerHTML = '';

      if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Aucun covoiturage trouvé.</div>';
        return;
      }

      container.innerHTML = data.map(trajet => {
        const badgeEco = Number(trajet.eco) === 1
          ? '<span class="badge badge-success ml-2">Écologique</span>' // Bootstrap 4
          : '';

        const note = (trajet.note_chauffeur ?? 'N/A');
        const noteText = renderStars(note);

        // lien détails : aussi relatif au dossier courant
        const detailsHref = absoluteUrl(`views/details.php?id=${encodeURIComponent(trajet.id)}`);

        return `
          <div class="d-flex justify-content-center mb-3">
            <div class="card shadow" style="max-width: 600px; width: 100%;">
              <div class="card-body d-flex flex-column text-center">
                <h5 class="card-title">${(trajet.chauffeur || '')} ${badgeEco}</h5>
                <p class="mb-1"><strong>Départ :</strong> ${trajet.depart || ''}</p>
                <p class="mb-1"><strong>Arrivée :</strong> ${trajet.destination || ''}</p>
                <p class="mb-1"><strong>Date :</strong> ${(trajet.date || '').slice(0,16)}</p>
                <p class="mb-1"><strong>Places restantes :</strong> ${trajet.places ?? ''}</p>
                <p class="mb-1"><strong>Prix :</strong> ${trajet.prix ?? ''} €</p>
                <p class="mb-2"><strong>Note :</strong> ${noteText} <small>(${note})</small></p>
                <a href="${detailsHref}" class="btn btn-info btn-sm">Détails</a>
              </div>
            </div>
          </div>
        `;
      }).join('');
    })
    .catch(err => {
      container.innerHTML = `<div class="alert alert-danger">❌ ${err.message}</div>`;
      console.error('Erreur AJAX:', err);
    });
}

// ===========================
// Listeners
// ===========================

document.getElementById('search-form')?.addEventListener('submit', (e) => {
  e.preventDefault();
  rechercherTrajets();
});

document.getElementById('appliquer-filtres')?.addEventListener('click', () => {
  rechercherTrajets();
});

// Optionnel : lancer une recherche automatique si champs déjà remplis
// window.addEventListener('DOMContentLoaded', () => {
//   if (val('depart') || val('destination') || val('date')) rechercherTrajets();
// });
