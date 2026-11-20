-- TP09 : compte SQL dédié au déploiement (aucune utilisation de root dans l'application).
-- À exécuter UNE SEULE FOIS avec un compte administrateur :
--   mysql -u root -p < TP09/sql/00_utilisateur.sql
--
-- Le mot de passe ci-dessous est fictif et réservé au TP. En production, le remplacer
-- par un secret long et le stocker hors du dépôt (le fichier .env n'est pas versionné).

CREATE DATABASE IF NOT EXISTS tp9_deploiement_test
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'stages_deploiement'@'127.0.0.1'
  IDENTIFIED BY 'Deploiement123!';

-- Privilèges strictement limités à une seule base : l'application peut créer ses
-- tables et manipuler ses données, mais ne peut ni lire les autres bases, ni
-- créer/supprimer des comptes, ni obtenir les privilèges LOCK TABLES (voir incident).
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER
  ON tp9_deploiement_test.* TO 'stages_deploiement'@'127.0.0.1';

FLUSH PRIVILEGES;

-- Contrôle :
--   SHOW GRANTS FOR 'stages_deploiement'@'127.0.0.1';
