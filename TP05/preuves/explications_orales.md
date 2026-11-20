# Explications orales : un commit par membre

Chaque membre doit pouvoir expliquer son commit à l'oral en montrant le diff et le test associé.

## Membre 1 — `db0d74e` « Initialise le depot et le README »

- **Fichier :** `README.md` (+5 lignes).
- **Ce que j'ai fait :** j'ai créé le dépôt avec `git init -b main`, ajouté le `.gitignore` pour exclure `.env` et les sauvegardes, puis validé le README de départ.
- **Pourquoi :** sans `.gitignore`, un mot de passe local pouvait être versionné ; le README donne les prérequis avant tout code.
- **Preuve :** `git show db0d74e` montre les 5 lignes ajoutées ; `git status` après le commit indique un arbre propre.

## Membre 2 — `0ec2ca8` « Ajoute la connexion PDO »

- **Fichier :** `config/database.php` (+2 lignes dans la simulation, squelette complet en TP4).
- **Ce que j'ai fait :** j'ai écrit la fabrique PDO avec `ATTR_ERRMODE => ERRMODE_EXCEPTION`, puis j'ai fusionné `feature/connexion` dans `main` après relecture du diff par un autre membre.
- **Pourquoi :** les exceptions évitent d'ignorer une erreur SQL, et le secret reste dans `.env` non versionné.
- **Test :** `php tests/test_connexion.php` doit afficher `Connexion PDO OK` ; sans base, le test échoue volontairement et ne révèle jamais le mot de passe.

## Membre 3 — `0befd36` « Ajoute POST /api/demandes »

- **Fichier :** `src/DemandeRepository.php` (opérations préparées, complétées en TP6).
- **Ce que j'ai fait :** j'ai ajouté la création de demande avec une requête préparée, sur une branche courte, puis demandé une revue avant fusion.
- **Pourquoi :** une requête préparée sépare le SQL des valeurs : c'est la défense de base contre l'injection SQL. Le contrat prévoit `201` en succès et `400`/`401` en erreur.
- **Preuve :** `git show 0befd36` et la ligne `0befd36 Ajoute POST /api/demandes` dans `git-log.txt`.
