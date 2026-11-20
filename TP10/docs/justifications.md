# Justification des choix techniques

Chaque choix est défendu par un argument **vérifiable** dans le dépôt, jamais par
« c'est ce que je connais ». Format demandé : deux lignes par choix (section 10.3).

## PDO — 2 lignes

`TP6/config/database.php` utilise PDO avec `ATTR_EMULATE_PREPARES => false` : les valeurs
sont envoyées séparément du texte SQL, ce qui rend l'injection impossible (cas 27 et 28
de la matrice : `1 OR 1=1` donne 404/400, aucune ligne créée).
Le déploiement ne demande qu'une extension PHP (`pdo_mysql`) et un fichier `.env`, vérifiés
par `/api/sante` sur les deux postes (`TP9/preuves/sante_http.txt`).

## Transaction de validation — 2 lignes

`DemandeService` enchaîne le changement de statut, l'écriture de `historique` et
l'éventuelle soutenance dans une seule transaction : un échec annule toutes les
modifications, donc aucun état partiel n'est visible.
La preuve est dans `TP8/docs/matrice_tests.md` : validation répétée → 409 et statut
inchangé en base (cas 17), soutenance sur demande non validée → 409 (cas 20).

## Contrôle d'accès côté serveur — 2 lignes

Chaque requête est authentifiée et autorisée dans le contrôleur/service, avant toute
lecture : le navigateur n'est jamais cru, seul le `PHPSESSID` et le rôle en base comptent.
L'étudiant B obtenant `403` sans donnée sur le dossier de A (cas 21) et la liste « mes
demandes » cloisonnée (cas 26) prouvent que le contrôle n'est pas seulement visuel.

## Modèle de données — 2 lignes

Une table par classe du diagramme (`etudiant`, `demande_stage`, `soutenance`, …) et des
clés étrangères dans `TP3/001_schema.sql` : l'intégrité des liens est vérifiée par MariaDB.
Le jeu d'essai est reproductible (`TP3/002_seed.sql`) et contrôlé par `003_verification.sql`
: 3 étudiants, 2 entreprises, 2 encadreurs, 4 demandes, 1 soutenance.

## Mot de passe et CSRF (rappel)

- `password_hash()` / `password_verify()` : hash `$2y$` vérifié directement en base
  (`TP8/docs/securite.md`).
- Jeton de 64 caractères en session, transmis dans `X-CSRF-Token` et comparé avec
  `hash_equals()` ; cookie `HttpOnly; SameSite=Lax` (`TP8/docs/securite.md`).
