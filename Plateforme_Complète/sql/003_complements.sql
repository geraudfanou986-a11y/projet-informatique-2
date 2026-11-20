-- TP6 - Tables complémentaires nécessaires à l'API (à exécuter après le TP3).
-- mysql -u root -p stages < sql/01_complements.sql

CREATE TABLE IF NOT EXISTS utilisateur (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(100) NOT NULL UNIQUE,
  hash VARCHAR(255) NOT NULL,        -- password_hash(), jamais de mot de passe en clair
  role VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS historique (
  id INT PRIMARY KEY AUTO_INCREMENT,
  demande_id INT NOT NULL,
  statut_avant VARCHAR(10) NOT NULL,
  statut_apres VARCHAR(10) NOT NULL,
  motif VARCHAR(255) NULL,
  utilisateur_id INT NULL,
  cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (demande_id) REFERENCES demande_stage(id),
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
);

-- Comptes fictifs : mots de passe hachés avec password_hash(..., PASSWORD_DEFAULT).
-- Etudiant123! / Responsable123! / Secretariat123!
INSERT INTO utilisateur (email, hash, role) VALUES
  ('koffi.adjovi@univ.test', '$2y$12$Q5c.v2gSqOlk8EyNLMqJQuopgOyCMSLbzirZJVF7eBuXMdmmMp8JS', 'etudiant'),
  ('sylvie.hounkpe@univ.test', '$2y$12$S.0qZ52hRFPfbP8l7s2CnOpDEHe6hxqU2bY1GSWkEmW0K/FIjbX16', 'responsable'),
  ('jean.akpo@univ.test', '$2y$12$HIJdQevwpjyHu14/MJI8fe5FNjmKRbwiF.AZgm/DNgJPKsTs0XIe.', 'secretariat');
