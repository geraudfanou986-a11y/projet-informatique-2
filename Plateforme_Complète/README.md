# Plateforme Complète — Gestion des stages

Version finale **autonome** du projet : une seule application web, une seule base de données et un installateur qui fait tout. Aucun composer, aucun dossier voisin, aucune manipulation manuelle du SQL n'est nécessaire pour un test chez vous.

L'application permet de créer une demande de stage, d'y joindre un document (PDF, JPG ou PNG), de la soumettre, de la valider, de programmer une soutenance et de consulter l'historique des changements, avec une interface web et une API JSON. Le bouton **Se déconnecter** reste visible en bas de la barre latérale pendant toute la session.

## Ce qui est fourni

- **L'application** : interface web complète (connexion/inscription, tableau de bord, demandes, soutenances, historique, profil) et API JSON des demandes, derrière un routeur unique.
- **La base de données** : quatre scripts SQL à importer dans l'ordre — schéma, jeu d'essai, compléments (tables `utilisateur` et `historique`) et table des documents joints.
- **Les fichiers** :
  - `README.md` — ce guide ;
  - `config/.env.example` — modèle de configuration (valeurs fictives) ;
  - `sql/001_schema.sql`, `sql/002_seed.sql`, `sql/003_complements.sql`, `sql/004_piece_jointe.sql` ;
  - `scripts/installer.php` — installateur web (création de la base, du compte applicatif, import SQL, écriture du `.env`) ;
  - `scripts/verifier.php` — contrôle après installation (tables et effectifs) ;
  - `.env` — créé par l'installateur, non fourni à l'avance. Le routeur `router.php` à la racine sert l'interface, l'API et l'installateur.

## Prérequis

- PHP **8.1 ou supérieur**, avec l'extension **pdo_mysql** active ;
- MySQL ou MariaDB accessible sur `127.0.0.1` (port `3306` par défaut) avec un compte administrateur ;
- un navigateur récent.

Vérifier PHP : `php -v`. Vérifier l'extension : `php -m | findstr pdo_mysql` (Windows) ou `php -m | grep pdo_mysql` (Linux/macOS).

## Installation en 3 étapes

1. Depuis ce dossier (`Plateforme_Complète`), démarrer le serveur PHP intégré :

   ```bash
   cd Plateforme_Complète
   php -S 127.0.0.1:8000 -t . router.php
   ```

   Laisser cette fenêtre ouverte pendant toute la démonstration.

2. Ouvrir <http://127.0.0.1:8000/installer.php> dans le navigateur et remplir le formulaire :
   hôte, port, nom de la base, utilisateur et mot de passe **applicatifs**, puis utilisateur et mot de passe **administrateurs MySQL**.
   L'installateur crée la base, un compte dédié (jamais `root` dans l'application), importe les quatre fichiers SQL et écrit le `.env`.
   Si la base contient déjà des tables, rien n'est touché : un bouton **Réinstaller** permet de repartir de zéro.

3. Cliquer sur le lien **Ouvrir l'application** affiché à la fin de l'installation (ou ouvrir <http://127.0.0.1:8000/>).
   Pour un contrôle rapide, ouvrir <http://127.0.0.1:8000/verifier.php>.

## Installation manuelle (ligne de commande)

À utiliser seulement si l'installateur web n'est pas disponible. Remplacer `root` et le mot de passe admin par vos identifiants MySQL, puis, depuis le dossier `Plateforme_Complète` :

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS stages_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'stages_app'@'localhost' IDENTIFIED BY 'StagesApp123!'; GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON stages_final.* TO 'stages_app'@'localhost'; FLUSH PRIVILEGES;"
mysql -u root -p stages_final < sql/001_schema.sql
mysql -u root -p stages_final < sql/002_seed.sql
mysql -u root -p stages_final < sql/003_complements.sql
mysql -u root -p stages_final < sql/004_piece_jointe.sql
copy config\.env.example .env
php -S 127.0.0.1:8000 -t . router.php
```

Sous Linux/macOS, remplacer `copy config\.env.example .env` par `cp config/.env.example .env`.
Si MariaDB écoute sur un autre port (par exemple 3307), ajouter `-P 3307` aux commandes `mysql` et modifier `DB_PORT=3307` dans le `.env`. Le `FLUSH PRIVILEGES` est facultatif sur MariaDB 10.4+ (les `GRANT` sont actifs immédiatement) ; l'installateur l'exécute mais ignore un éventuel échec.

## Comptes fictifs de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Étudiant | `koffi.adjovi@univ.test` | `Etudiant123!` |
| Responsable | `sylvie.hounkpe@univ.test` | `Responsable123!` |
| Secrétariat | `jean.akpo@univ.test` | `Secretariat123!` |

Ces comptes sont créés par le script `sql/003_complements.sql`. Les mots de passe sont hachés dans la base.

## Parcours de test en 7 étapes

1. **Connexion étudiant** — ouvrir l'application, se connecter avec `koffi.adjovi@univ.test` / `Etudiant123!` ; la liste « Mes demandes » affiche les demandes de Koffi.
2. **Dépôt avec document** — créer une nouvelle demande en choisissant une entreprise ; vous pouvez joindre immédiatement une photo de la convention ou un PDF. La demande apparaît au statut `BROUILLON` avec l'indicateur 📎.
3. **Document joint** — dans « Détail », ouvrez/téléchargez le fichier, remplacez-le ou retirez-le. Un autre étudiant ne peut pas y accéder (403).
4. **Soumission** — passer cette demande de `BROUILLON` à `SOUMISE` ; le bouton de validation devient disponible côté responsable.
5. **Validation par le responsable** — se connecter avec `sylvie.hounkpe@univ.test` / `Responsable123!`, puis valider la demande : statut `VALIDEE` et ligne d'historique `SOUMISE → VALIDEE`.
6. **Programmation de la soutenance et contrôle d'accès** — avec le compte secrétariat (ou responsable), saisir une date et une salle ; la soutenance apparaît dans l'agenda. Avec un deuxième compte étudiant, tenter d'ouvrir le dossier de Koffi : le serveur renvoie **403 / « Cette demande ne vous est pas accessible »**, même en forçant l'adresse.
7. **Déconnexion** — cliquer sur **Se déconnecter** en bas de la barre latérale : le cookie de session est supprimé et l'accueil revient.

Astuce : les onglets d'un même navigateur partagent la session. Pour observer les deux rôles en direct, utiliser deux navigateurs ou une fenêtre privée. Après une validation, rafraîchir la page (F5) : le statut relu depuis la base doit rester `VALIDEE`.

## Documents joints

- Formats acceptés : **PDF, JPG et PNG**, **2 Mo maximum** (valeur par défaut de `upload_max_filesize` dans `php.ini`). Pour autoriser des fichiers plus gros, augmenter `upload_max_filesize` et `post_max_size` côté PHP.
- Le type est vérifié sur le **contenu** du fichier (`finfo`) et l'extension doit correspondre : un fichier HTML renommé `.png` est refusé.
- Les fichiers sont enregistrés dans `stockage/`, **hors du dossier `public/`** : ils ne sont jamais servis directement par le routeur. Le téléchargement passe par `/api/demandes/{id}/piece`, avec la même session et le même contrôle d'accès que le détail de la demande.
- Seul l'étudiant propriétaire peut ajouter, remplacer ou retirer un document ; le responsable et le secrétariat peuvent le consulter. Toute écriture est protégée par le jeton CSRF.
- La base ne contient que les métadonnées (nom, type, taille) dans la table `piece_jointe` ; le contenu du fichier n'est pas stocké en SQL.

## Dépannage

- **Le port 8000 est déjà utilisé** — choisir un autre port, par exemple `php -S 127.0.0.1:8001 -t . router.php`, puis ouvrir `http://127.0.0.1:8001/installer.php`. Si le port MySQL n'est pas 3306, renseigner le bon port dans le formulaire et dans `.env`.
- **Le `.env` a été oublié ou supprimé** — relancer `http://127.0.0.1:8000/installer.php` : il est réécrit automatiquement. En installation manuelle, copier `config/.env.example` vers `.env` à la racine puis adapter les valeurs.
- **Erreur de connexion à la base** — vérifier que MySQL/MariaDB est démarré, que le port est correct et que le mot de passe du compte `stages_app` dans `.env` correspond à celui saisi lors de l'installation. Le script <http://127.0.0.1:8000/verifier.php> indique précisément ce qui manque.
- **La page d'installation affiche « base déjà installée »** — c'est une sécurité, pas une erreur. Cliquer sur **Réinstaller** pour supprimer puis recréer uniquement la base nommée dans le formulaire.
- **Réinitialiser complètement** — relancer l'installateur avec le même nom de base et cliquer sur **Réinstaller** ; les quatre scripts SQL sont réimportés dans l'ordre et les données fictives reprennent leur état initial. En ligne de commande : `mysql -u root -p -e "DROP DATABASE IF EXISTS stages_final;"` puis refaire l'installation.
- **Erreur 500 côté application** — contrôler le `.env`, puis `php -S 127.0.0.1:8000 -t . router.php` depuis le bon dossier (la racine de la plateforme, pas un sous-dossier).

## Contenu du dossier

| Dossier | Rôle |
|---|---|
| `sql/` | Les quatre scripts de base : schéma, jeu d'essai, compléments (utilisateurs et historique), documents joints. |
| `config/` | Modèle de configuration `.env.example` et configuration PDO de l'application. |
| `api/` | API des demandes : contrôleurs, services, dépôts, règles de transition et réponses JSON. |
| `public/` | Interface web (pages HTML, CSS, JavaScript) et point d'entrée de l'application. |
| `scripts/` | Installateur web et script de vérification post-installation. |
| `stockage/` | Documents joints envoyés depuis l'interface (hors `public/`, non versionnés). |
| `preuves/` | Résultats des tests et captures du scénario de démonstration. |

À la racine se trouvent aussi `router.php` (point d'entrée du serveur), le `.env` généré lors de l'installation et ce `README.md`.

> Toutes les données et tous les comptes présentés ici sont **fictifs**. Ce projet est un **projet pédagogique** réalisé dans le cadre d'un module de développement web ; il ne doit pas être utilisé en production tel quel.
