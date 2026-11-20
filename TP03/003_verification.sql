-- TP03 : vérification du jeu d'essai
-- Exécution : mysql -u root -p stages < 003_verification.sql
-- Attendu : etudiant=3, entreprise=2, encadreur=2, demande_stage=4, soutenance=1

SELECT 'etudiant' AS t, COUNT(*) AS n FROM etudiant
UNION ALL SELECT 'entreprise',    COUNT(*) FROM entreprise
UNION ALL SELECT 'encadreur',     COUNT(*) FROM encadreur
UNION ALL SELECT 'demande_stage', COUNT(*) FROM demande_stage
UNION ALL SELECT 'soutenance',    COUNT(*) FROM soutenance;

-- Test de la contrainte UNIQUE (à lancer à la main, il DOIT échouer) :
-- INSERT INTO soutenance (demande_id, date_heure, salle) VALUES (2, '2026-12-11 10:00:00', 'B13');
-- Attendu : ERROR 1062 (23000): Duplicate entry '2' for key 'soutenance.demande_id'
