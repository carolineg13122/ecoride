-- Conversion des tables MySQL vers PostgreSQL

-- Table users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(10),
    mot_de_passe VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    credits INTEGER DEFAULT 20,
    role VARCHAR(50) DEFAULT 'utilisateur',
    photo BYTEA
);

-- Table vehicules
CREATE TABLE vehicules (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    marque VARCHAR(100),
    modele VARCHAR(100),
    couleur VARCHAR(100),
    energie VARCHAR(50),
    plaque_immatriculation VARCHAR(20),
    date_immatriculation DATE
);

-- Table trajets
CREATE TABLE trajets (
    id SERIAL PRIMARY KEY,
    chauffeur VARCHAR(100) NOT NULL,
    photo VARCHAR(255) DEFAULT 'default.jpg',
    depart VARCHAR(100) NOT NULL,
    adresse_depart VARCHAR(255),
    destination VARCHAR(100) NOT NULL,
    adresse_arrivee VARCHAR(255),
    date DATE NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    places INTEGER DEFAULT 3,
    vehicule_id INTEGER REFERENCES vehicules(id) ON DELETE CASCADE,
    eco BOOLEAN DEFAULT FALSE,
    fumeur BOOLEAN DEFAULT FALSE,
    preferences TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    statut VARCHAR(20) DEFAULT 'à_venir',
    duree_minutes INTEGER DEFAULT 0
);

-- Table reservations
CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    trajet_id INTEGER REFERENCES trajets(id) ON DELETE CASCADE,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table avis
CREATE TABLE avis (
    id SERIAL PRIMARY KEY,
    trajet_id INTEGER REFERENCES trajets(id) ON DELETE CASCADE,
    utilisateur_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    note DECIMAL(2,1) CHECK (note >= 0 AND note <= 5),
    commentaire TEXT,
    valide BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table confirmations
CREATE TABLE confirmations (
    id SERIAL PRIMARY KEY,
    id_trajet INTEGER REFERENCES trajets(id) ON DELETE CASCADE,
    id_passager INTEGER REFERENCES users(id) ON DELETE CASCADE,
    statut VARCHAR(20) DEFAULT 'valide',
    commentaire TEXT,
    note INTEGER,
    avis TEXT,
    valide BOOLEAN DEFAULT FALSE,
    traite_par INTEGER REFERENCES users(id) ON DELETE CASCADE,
    date_validation TIMESTAMP
); 