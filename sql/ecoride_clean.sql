
-- Script SQL structuré pour la plateforme EcoRide

-- =========================
-- Table : users
-- =========================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('chauffeur', 'passager', 'admin') DEFAULT 'passager',
    credits INT DEFAULT 20,
    photo TEXT
);

-- =========================
-- Table : vehicules
-- =========================
CREATE TABLE vehicules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    marque VARCHAR(100),
    modele VARCHAR(100),
    energie VARCHAR(50),
    plaque_immatriculation VARCHAR(20),
    date_immatriculation DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- Table : trajets
-- =========================
CREATE TABLE trajets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicule_id INT,
    depart VARCHAR(100),
    adresse_depart TEXT,
    destination VARCHAR(100),
    adresse_arrivee TEXT,
    date DATE,
    duree_minutes INT,
    places INT,
    prix DECIMAL(6,2),
    statut ENUM('à venir', 'en cours', 'terminé', 'annulé') DEFAULT 'à venir',
    preferences TEXT,
    eco BOOLEAN DEFAULT FALSE,
    fumeur BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vehicule_id) REFERENCES vehicules(id)
);

-- =========================
-- Table : reservations
-- =========================
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trajet_id INT NOT NULL,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trajet_id) REFERENCES trajets(id)
);

-- =========================
-- Table : avis
-- =========================
CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    trajet_id INT NOT NULL,
    commentaire TEXT,
    note TINYINT,
    valide BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (utilisateur_id) REFERENCES users(id),
    FOREIGN KEY (trajet_id) REFERENCES trajets(id)
);

-- =========================
-- Table : confirmations
-- =========================
CREATE TABLE confirmations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_trajet INT NOT NULL,
    id_passager INT NOT NULL,
    valide BOOLEAN,
    commentaire TEXT,
    statut ENUM('ok', 'signalé') DEFAULT 'ok',
    note TINYINT,
    avis TEXT,
    traite_par INT,
    date_validation DATETIME,
    FOREIGN KEY (id_trajet) REFERENCES trajets(id),
    FOREIGN KEY (id_passager) REFERENCES users(id),
    FOREIGN KEY (traite_par) REFERENCES users(id)
);

-- =========================
-- Jeux de données de test
-- =========================

INSERT INTO users (prenom, nom, email, mot_de_passe, role, credits)
VALUES ('Caroline', 'Gautier', 'caroline@example.com', 'motdepassehashé', 'chauffeur', 20);

INSERT INTO vehicules (user_id, marque, modele, energie, plaque_immatriculation, date_immatriculation)
VALUES (1, 'Renault', 'ZOE', 'Électrique', 'AB-123-CD', '2022-06-01');

INSERT INTO trajets (user_id, vehicule_id, depart, adresse_depart, destination, adresse_arrivee, date, duree_minutes, places, prix)
VALUES (1, 1, 'Marseille', 'Gare Saint-Charles', 'Aix-en-Provence', 'Place de la Rotonde', '2025-07-25', 45, 3, 6.50);

INSERT INTO reservations (user_id, trajet_id)
VALUES (1, 1);
