// Fonction pour afficher les étoiles en fonction de la note
function renderStars(note) {
    if (!note || note === 'N/A') return "N/A";

    const fullStars = Math.floor(note);
    const halfStar = note % 1 >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;

    return '⭐'.repeat(fullStars) + (halfStar ? '✨' : '') + '☆'.repeat(emptyStars);
}

// Fonction principale de recherche AJAX
function rechercherTrajets() {
    const depart = document.getElementById("depart").value;
    const destination = document.getElementById("destination").value;
    const date = document.getElementById("date").value;

    const filtreEco = document.getElementById("filtre-eco").value;
    const filtrePrix = document.getElementById("filtre-prix").value;
    const filtreDuree = document.getElementById("filtre-duree").value;
    const filtreNoteElement = document.getElementById("filtre-note");
    const filtreNote = filtreNoteElement ? filtreNoteElement.value : "";

    const params = new URLSearchParams({
        depart: depart,
        destination: destination,
        date: date,
        filtreEco: filtreEco,
        filtrePrix: filtrePrix,
        filtreDuree: filtreDuree,
        filtreNote: filtreNote
    });

    fetch(`rechercher.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("liste-covoiturages");
            container.innerHTML = "";

            if (data.length === 0) {
                container.innerHTML = "<p>Aucun covoiturage trouvé.</p>";
                return;
            }

            data.forEach(trajet => {
                const badgeEco = trajet.eco == 1 
                    ? '<span class="badge bg-success">Écologique</span>' 
                    : '';

                const noteText = renderStars(trajet.note_chauffeur);
                const card = `
                    <div class="d-flex justify-content-center mb-3">
                       <div class="card shadow" style="max-width: 600px; width: 100%;">
                        <div class="card-body d-flex flex-column text-center">

                            <div>
                            <h5 class="card-title">${trajet.chauffeur} ${badgeEco}</h5>
                            <p><strong>Départ :</strong> ${trajet.depart}</p>
                            <p><strong>Arrivée :</strong> ${trajet.destination}</p>
                            <p><strong>Date :</strong> ${trajet.date}</p>
                            <p><strong>Places restantes :</strong> ${trajet.places}</p>
                            <p><strong>Prix :</strong> ${trajet.prix}€</p>
                            <p><strong>Note :</strong> ${noteText} (${trajet.note_chauffeur ?? 'N/A'})</p>
                            <a href="details.php?id=${trajet.id}" class="btn btn-info">Détails</a>
                            </div>
                        </div>
                        </div>
                    </div>
                    `;

                container.innerHTML += card;
            });
        })
        .catch(error => {
            console.error("Erreur AJAX :", error);
            document.getElementById("liste-covoiturages").innerHTML = "<p>Erreur lors de la recherche.</p>";
        });
}

// Recherche au submit du formulaire
document.getElementById("search-form").addEventListener("submit", function (e) {
    e.preventDefault();
    rechercherTrajets();
});

// Recherche aussi en cliquant sur "Appliquer les filtres"
document.getElementById("appliquer-filtres").addEventListener("click", function () {
    rechercherTrajets();
});
