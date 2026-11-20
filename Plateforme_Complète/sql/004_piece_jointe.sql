-- Complément final : document joint facultatif sur une demande de stage.
-- Un seul document par demande : la clé primaire sur demande_id garantit l'unicité.
-- Le fichier lui-même n'est PAS stocké dans la base ni dans le dossier public/ :
-- il est écrit dans stockage/ (hors de la racine servie par router.php).

CREATE TABLE IF NOT EXISTS piece_jointe (
  demande_id   INT NOT NULL,
  nom_stockage VARCHAR(64) NOT NULL,
  nom_original VARCHAR(255) NOT NULL,
  type_mime    VARCHAR(60) NOT NULL,
  taille       INT NOT NULL,
  cree_le      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (demande_id),
  FOREIGN KEY (demande_id) REFERENCES demande_stage(id) ON DELETE CASCADE
);
