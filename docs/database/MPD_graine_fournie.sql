-- ============================================================
-- MPD - GRAINE FOURNIE AQUIPLANTS (version 2)
-- Base de données : MySQL 8.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS graine_fournie_aquiplants
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE graine_fournie_aquiplants;

-- ------------------------------------------------------------
-- Table : UTILISATEUR
-- ------------------------------------------------------------
CREATE TABLE utilisateur (
    id_utilisateur INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mdp_crypte VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employe') NOT NULL DEFAULT 'employe',
    CONSTRAINT pk_utilisateur PRIMARY KEY (id_utilisateur),
    CONSTRAINT uq_utilisateur_email UNIQUE (email)
);

-- ------------------------------------------------------------
-- Table : CLIENT
-- ------------------------------------------------------------
CREATE TABLE client (
    id_client INT NOT NULL AUTO_INCREMENT,
    nom_client VARCHAR(150) NOT NULL,
    prenom_client VARCHAR(150) NOT NULL,
    CONSTRAINT pk_client PRIMARY KEY (id_client)
);

-- ------------------------------------------------------------
-- Table : PLANT
-- ------------------------------------------------------------
CREATE TABLE plant (
    id_plant INT NOT NULL AUTO_INCREMENT,
    nom_plant VARCHAR(150) NOT NULL,
    nom_espece VARCHAR(150) NOT NULL,
    CONSTRAINT pk_plant PRIMARY KEY (id_plant)
);

-- ------------------------------------------------------------
-- Table : UV
-- ------------------------------------------------------------
CREATE TABLE uv (
    id_uv INT NOT NULL AUTO_INCREMENT,
    nom_uv VARCHAR(100) NOT NULL,
    nb_graine_par_motte INT NOT NULL,
    CONSTRAINT pk_uv PRIMARY KEY (id_uv)
);

-- ------------------------------------------------------------
-- Table : GF_CLIENT
-- ------------------------------------------------------------
CREATE TABLE gf_client (
    id_gf_client INT NOT NULL AUTO_INCREMENT,
    reference_gf VARCHAR(50) NOT NULL,
    quantite_disponible INT NOT NULL DEFAULT 0,
    seuil_alerte INT NOT NULL DEFAULT 0,
    nom_client VARCHAR(150) NOT NULL,
    id_client INT NOT NULL,
    id_plant INT NULL,
    CONSTRAINT pk_gf_client PRIMARY KEY (id_gf_client),
    CONSTRAINT uq_gf_reference UNIQUE (reference_gf),
    CONSTRAINT fk_gf_client_client FOREIGN KEY (id_client)
        REFERENCES client (id_client)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_gf_client_plant FOREIGN KEY (id_plant)
        REFERENCES plant (id_plant)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ------------------------------------------------------------
-- Table : HISTO_GF_DEPOSEE
-- ------------------------------------------------------------
CREATE TABLE histo_gf_deposee (
    id_histo_depot INT NOT NULL AUTO_INCREMENT,
    quantite_deposee INT NOT NULL,
    date_reception DATE NOT NULL,
    statut ENUM('a_traiter', 'range') NOT NULL DEFAULT 'a_traiter',
    note VARCHAR(255) NULL,
    id_gf_client INT NOT NULL,
    CONSTRAINT pk_histo_gf_deposee PRIMARY KEY (id_histo_depot),
    CONSTRAINT fk_histo_depot_gf_client FOREIGN KEY (id_gf_client)
        REFERENCES gf_client (id_gf_client)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- ------------------------------------------------------------
-- Table : GF_HISTO_CLIENT
-- ------------------------------------------------------------
CREATE TABLE gf_histo_client (
    id_histo INT NOT NULL AUTO_INCREMENT,
    quantite_semee INT NOT NULL,
    date_semis DATE NOT NULL,
    nom_uv VARCHAR(100) NOT NULL,
    nb_graine_par_motte INT NOT NULL,
    id_gf_client INT NOT NULL,
    id_uv INT NOT NULL,
    CONSTRAINT pk_gf_histo_client PRIMARY KEY (id_histo),
    CONSTRAINT fk_histo_gf_client FOREIGN KEY (id_gf_client)
        REFERENCES gf_client (id_gf_client)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_histo_uv FOREIGN KEY (id_uv)
        REFERENCES uv (id_uv)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- ------------------------------------------------------------
-- Table : COMMANDE_A_SEMER
-- ------------------------------------------------------------
CREATE TABLE commande_a_semer (
    id_commande INT NOT NULL AUTO_INCREMENT,
    quantite_a_semer INT NOT NULL,
    date_semis DATE NOT NULL,
    date_livraison DATE NOT NULL,
    id_uv INT NOT NULL,
    id_client INT NOT NULL,
    CONSTRAINT pk_commande_a_semer PRIMARY KEY (id_commande),
    CONSTRAINT fk_commande_uv FOREIGN KEY (id_uv)
        REFERENCES uv (id_uv)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_commande_client FOREIGN KEY (id_client)
        REFERENCES client (id_client)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- ------------------------------------------------------------
-- Table : EMPLACEMENT
-- ------------------------------------------------------------
CREATE TABLE emplacement (
    id_emplacement INT NOT NULL AUTO_INCREMENT,
    lettre_etagere ENUM('A', 'B', 'C', 'D') NOT NULL,
    numero_etage INT NOT NULL,
    id_gf_client INT NOT NULL,
    CONSTRAINT pk_emplacement PRIMARY KEY (id_emplacement),
    CONSTRAINT uq_emplacement UNIQUE (lettre_etagere, numero_etage, id_gf_client),
    CONSTRAINT fk_emplacement_gf_client FOREIGN KEY (id_gf_client)
        REFERENCES gf_client (id_gf_client)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- ------------------------------------------------------------
-- Table : LOG
-- ------------------------------------------------------------
CREATE TABLE log (
    id_log INT NOT NULL AUTO_INCREMENT,
    action VARCHAR(100) NOT NULL,
    date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detail VARCHAR(255) NULL,
    id_utilisateur INT NOT NULL,
    CONSTRAINT pk_log PRIMARY KEY (id_log),
    CONSTRAINT fk_log_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur)
        ON DELETE RESTRICT ON UPDATE CASCADE
);