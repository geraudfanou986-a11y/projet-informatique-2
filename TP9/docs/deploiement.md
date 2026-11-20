# TP9 — Déploiement sur un second poste

## Protocole suivi

1. **Clone propre** dans un dossier temporaire séparé,
   `.../tp9_poste2/projet-informatique-2`
   (aucun fichier local, aucun `.env` recopié depuis le poste principal).
2. **Lecture du seul README racine** pour tenter l'installation ; chaque écart rencontré
   a été noté avant d'être corrigé.
3. **Création d'un utilisateur SQL dédié** via `TP9/sql/00_utilisateur.sql`.
4. **Configuration** de `TP6/.env` depuis `TP9/config/.env.exemple`.
5. **Import** des trois scripts dans la base dédiée avec le compte limité.
6. **Démarrage** du serveur sur le port 8002 et appel de `/api/sante`.
7. **Sauvegarde et restauration** (voir `docs/restauration.md`).
8. **Ticket d'incident** reproductible (voir `docs/incident.md`).

Le serveur du poste principal (`127.0.0.1:8001`) est resté en service pendant toute la
manipulation : les deux postes simulés étaient indépendants et simultanés.

## Écarts trouvés dans le README racine

| # | Étape du README telle qu'écrite | Problème rencontré | Correction apportée |
|---|---|---|---|
| 1 | `mysql -u root -p ...` pour créer la base et importer | Le TP9 exige un compte dédié, pas `root`. Aucun script de création d'utilisateur n'existait dans le dépôt. | Ajout de `TP9/sql/00_utilisateur.sql` et d'une procédure qui n'utilise `root` **que** pour créer le compte, jamais pour l'application. |
| 2 | `mysql -u root -p` sans hôte ni port | L'environnement du cours écoute sur `127.0.0.1:3307`, pas sur le port par défaut `3306` (`mysql` se connecte par socket/3306 et échoue). | Renseigner explicitement `-h 127.0.0.1 -P 3307` dans toutes les commandes et dans `DB_PORT` du `.env`. |
| 3 | `cd TP6 && cp .env.example .env` puis, plus bas, démarrage de TP7 | Le serveur combiné TP7 délègue `/api/*` à `TP6/public/index.php` (voir `TP7/public/router.php`). Si l'on démarre seulement TP7 sans avoir créé `TP6/.env`, l'API retombe sur les valeurs par défaut (`root`, `3306`, `stages`) et `/api/sante` répond une erreur. | Préciser que **`TP6/.env` est obligatoire même quand on ne démarre que TP7**, et fournir `TP9/config/.env.exemple`. |
| 4 | Aucune procédure de sauvegarde | Le TP9 demande `mysqldump`, suppression d'une table, restauration et comparaison des `COUNT(*)`. | Ajout de `docs/restauration.md` et de preuves avant/après. |
| 5 | Aucune note sur `mysqldump` avec un compte limité | Le compte dédié n'a pas `LOCK TABLES` ; le `mysqldump` par défaut échoue avec `ERROR 1044 ... when using LOCK TABLES` (voir incident). | Documenter `--single-transaction --skip-add-locks` comme commande de référence. |

Le README racine a été mis à jour pour renvoyer vers ce dossier et ne plus laisser croire
que `root` est la configuration normale. Le numéro de groupe n'a pas été réintroduit.

## Compte dédié — vérification

```sql
CREATE USER IF NOT EXISTS 'stages_deploiement'@'127.0.0.1'
  IDENTIFIED BY 'Deploiement123!';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER
  ON tp9_deploiement_test.* TO 'stages_deploiement'@'127.0.0.1';
```

Privilèges observés (`preuves/grants.txt`) :

```
GRANT USAGE ON *.* TO `stages_deploiement`@`127.0.0.1` ...
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON `tp9_deploiement_test`.* TO ...
```

Le compte peut créer et supprimer ses tables (`CREATE TABLE` / `DROP TABLE` vérifiés),
mais une connexion à `tp7_corrige_test` est refusée :

```
SQLSTATE[HY000] [1044] Access denied for user 'stages_deploiement'@'127.0.0.1'
to database 'tp7_corrige_test'
```

(`preuves/restriction.txt`.) Il n'a donc aucune visibilité sur les autres bases du poste.

## Import et santé

Journal réel (`preuves/import.log`) :

```
OK  TP3/001_schema.sql
OK  TP3/002_seed.sql
OK  TP6/sql/01_complements.sql
```

Serveur lancé sur le second poste :

```bash
php -S 127.0.0.1:8002 -t TP7/public TP7/public/router.php
```

Réponse réelle (`preuves/sante_http.txt`) :

```
HTTP 200 {"statut":"ok","base":"joignable"}
```

La page de santé ne se contente pas de répondre : `TP6/public/index.php` ouvre une
connexion PDO à `tp9_deploiement_test`, donc `200` prouve que le compte dédié, le `.env`,
le port et l'import sont corrects.
