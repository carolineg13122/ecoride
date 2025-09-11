# 🚗 EcoRide – Plateforme de covoiturage écologique

EcoRide est une application web de covoiturage respectueuse de l'environnement, permettant à tout utilisateur de publier ou réserver un trajet.  
Des interfaces dédiées aux chauffeurs, passagers, employés et administrateurs sont disponibles.

---

## 🧱 Technologies utilisées

- PHP 8 avec PDO (sécurité SQL)
- MySQL / MariaDB
- Bootstrap 5 pour le responsive
- JavaScript (AJAX) pour la recherche dynamique
- Hébergement local : XAMPP (Apache + MySQL)

---

## ⚙️ Installation locale (XAMPP)

1. Cloner ce dépôt ou copier les fichiers dans `C:/xampp/htdocs/ecoride`
2. Démarrer Apache et MySQL via le panneau de contrôle XAMPP
3. Créer une base de données nommée `ecoride` via [phpMyAdmin](http://localhost/phpmyadmin)
4. Importer le fichier SQL de création (à placer dans un dossier `sql/` si non présent)
5. Vérifier `config/database.php` :
```php
$host = 'localhost';
$dbname = 'ecoride';
$username = 'root';
$password = '';
```
6. Lancer l’application : [http://localhost/ecoride](http://localhost/ecoride)http://localhost:8000/index.php

---
## ⚙️ Utilisation avec Docker (alternative à XAMPP)
Ce projet peut également être exécuté via un conteneur Docker, sans XAMPP.
1.Construction de l’image
Depuis le répertoire racine du projet (où se trouve le Dockerfile) :
docker build -t ecoride-app .
2. Lancement du conteneur
docker run -d -p 8080:80 ecoride-app
L'application sera accessible sur http://localhost:8080

⚠️ Ce conteneur inclut PHP et Apache, mais nécessite que la base de données soit déjà accessible (par exemple avec XAMPP ou Railway).
## 🔐 Comptes de test

| Rôle        | Email                  | Mot de passe |
|-------------|------------------------|--------------|
| Utilisateur | user1@ecoride.fr       | azerty123    |
| Utilisateur | user2@ecoride.fr       | azerty123    |
| Employé     | employe@ecoride.fr     | azerty123    |
| Admin       | admin@ecoride.fr       | azerty123    |

---

## 📁 Structure du projet

```
ecoride/
├── .vscode/                    # Paramétrage de l’environnement VS Code
├── assets/                    # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   └── database.php           # Connexion base de données
├── controllers/               # Fichiers de traitement (backend PHP)
│   ├── connexion.php
│   ├── traiter_*.php
│   └── ...
├── views/                     # Interfaces utilisateur (frontend)
│   ├── ajouter_trajet.php
│   ├── rechercher.php
│   ├── profil.php
│   └── ...
├── models/                    # (optionnel, à compléter si POO)
├── templates/                 # Header, footer
├── sql/
│   ├── ecoride_full.sql       # Script SQL
│   └── convert_mysql_to_pg.sql
├── dockerfile                 # Fichier de build Docker
├── docker-compose.yml         # configuration complémentaire
├── composer.json              # Dépendances PHP 
├── index.php                  # Page d’accueil
├── README.md
└── docs/          
---

## 🧪 Fonctionnalités principales

- Gestion des rôles : utilisateur, employé, admin
- Inscription / connexion sécurisée
- Filtres de recherche (prix, durée, éco, note)
- Recherche dynamique de trajets avec fetch() et rendu asynchrone côté JavaScript
- Saisie d’un trajet avec préférences
- Réservations et gestion des crédits
- Interface employé (validation avis, signalement)
- Statistiques administrateur (crédits, trajets)

---

## ✅ Git Workflow attendu

- Branche `main` : version stable
- Branche `feature/NOM : 1 branche par fonctionnalité 
- Merge dans `main`  `main` après validation

---

## 📚 Documentation incluse

- Manuel utilisateur (PDF)
- Charte graphique (PDF avec maquettes et couleurs)
- Diagrammes (MCD,diagramme d'utilisation, diagramme de séquence)
- Fichier SQL de création + jeu de données
- Fichier SQL structuré : `sql/ecoride_clean.sql`

---
---

## 🧪 Bonus : Simulation NoSQL (MongoDB)

Pour répondre au référentiel du TP Développeur Web & Web Mobile, une simulation de base NoSQL a été intégrée.  
Un script PHP (`simuler_nosql.php`) permet d’enregistrer des avis de trajets dans un fichier JSON (`nosql/avis.json`), comme le ferait une base MongoDB.

**Exemple de structure JSON :**

```json
{
  "utilisateur": "Caroline",
  "trajet_id": 42,
  "note": 5,
  "commentaire": "Super chauffeur 👍",
  "timestamp": "2025-09-11 14:00:00"
}

## 👨‍💻 Auteur

Projet réalisé dans le cadre du TP Développeur Web et Web Mobile (DWWM) – ECF 2025.