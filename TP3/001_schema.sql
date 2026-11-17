-- TP3 : schéma de la base "stages" (MySQL / MariaDB)
-- Exécution : mysql -u root -p stages < 001_schema.sql

CREATE TABLE etudiant (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(60) NOT NULL,
  prenom VARCHAR(60) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE entreprise (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(80) NOT NULL,
  ville VARCHAR(60)
);

CREATE TABLE encadreur (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(60) NOT NULL,
  prenom VARCHAR(60) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE demande_stage (
  id INT PRIMARY KEY AUTO_INCREMENT,
  etudiant_id INT NOT NULL,
  entreprise_id INT NOT NULL,
  encadreur_id INT NULL,                       -- 0..1 encadreur (EF-04)
  statut VARCHAR(10) NOT NULL DEFAULT 'BROUILLON',
  motif_refus VARCHAR(255) NULL,
  FOREIGN KEY (etudiant_id) REFERENCES etudiant(id),
  FOREIGN KEY (entreprise_id) REFERENCES entreprise(id),
  FOREIGN KEY (encadreur_id) REFERENCES encadreur(id)
);

CREATE TABLE soutenance (
  id INT PRIMARY KEY AUTO_INCREMENT,
  demande_id INT NOT NULL UNIQUE,              -- UNIQUE = 0..1 soutenance par demande
  date_heure DATETIME NOT NULL,
  salle VARCHAR(20),
  FOREIGN KEY (demande_id) REFERENCES demande_stage(id)
);
