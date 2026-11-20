# TP9 — Déploiement et sauvegarde

Installation du projet sur un **second poste simulé** en suivant uniquement le README,
création d'un **utilisateur SQL dédié** (jamais `root`), import des scripts, vérification
de la page de santé, puis **sauvegarde / destruction / restauration** avec comparaison
des `COUNT(*)`. Données fictives uniquement.

## Contenu

| Fichier | Rôle |
|---|---|
| `docs/deploiement.md` | Compte rendu du déploiement sur le second poste, écarts trouvés dans le README et corrections apportées |
| `docs/restauration.md` | Procédure `mysqldump`, suppression d'une table, restauration, comparaison des `COUNT(*)` |
| `docs/incident.md` | Ticket d'incident reproductible (erreur 1044 sur `LOCK TABLES`) avec cause, correctif et prévention |
| `sql/00_utilisateur.sql` | Création du compte limité et de ses privilèges (à exécuter une fois, en `root`) |
| `config/.env.exemple` | Configuration du second poste avec le compte dédié (aucun `root`) |
| `scripts/verifier_deploiement.php` | Vérification reproductible : santé HTTP, compte limité, périmètre, `COUNT(*)` |
| `preuves/sante_http.txt` | Réponse réelle de `GET /api/sante` sur le second poste (`200`) |
| `preuves/grants.txt` | Privilèges effectifs du compte `stages_deploiement` |
| `preuves/import.log` | Journal de l'import des trois scripts SQL |
| `preuves/avant.json`, `preuves/apres.json` | `COUNT(*)` avant et après restauration (identiques) |
| `preuves/restriction.txt` | Preuve que le compte ne peut pas accéder à une autre base |
| `preuves/incident.txt` | Message d'erreur brut du `mysqldump` défaillant |
| `preuves/resultat_verification.txt` | Sortie du script de vérification final |
| `preuves/resultat_verification.json` | Même vérification, exploitable par un programme |

## Environnement du second poste

- Dépôt cloné à part dans un dossier temporaire séparé :
  `.../tp9_poste2/projet-informatique-2` (clone propre, aucun fichier local non versionné).
- PHP 8.5.8 (CLI), MariaDB 10.4.32 sur `127.0.0.1:3307`.
- Serveur de développement du second poste : `http://127.0.0.1:8002`
  (le serveur du poste principal sur `8001` n'a pas été arrêté).
- Base de déploiement : `tp9_deploiement_test`.
- Base de restauration : `tp9_restauration_test`.

## Résultat

- **Étapes manquantes du README corrigées** : 5 écarts documentés dans `docs/deploiement.md`.
- **Compte dédié** `stages_deploiement` créé : seuls `SELECT, INSERT, UPDATE, DELETE, CREATE,
  DROP, INDEX, ALTER` sur **une seule base** ; tout accès à une autre base refusé (preuve
  `preuves/restriction.txt`).
- **Santé** : `HTTP 200 {"statut":"ok","base":"joignable"}` (`preuves/sante_http.txt`).
- **Restauration** : 7 tables, `COUNT(*)` identiques avant et après
  (`preuves/avant.json` = `preuves/apres.json`).
- **Incident** reproductible trouvé et corrigé : `docs/incident.md`.

## Reprendre chez soi

```bash
# 1. Cloner et créer le compte limité (une seule fois, en root)
mysql -u root -p < TP9/sql/00_utilisateur.sql

# 2. Configurer le second poste
cp TP9/config/.env.exemple TP6/.env
# adapter DB_PORT / DB_NAME si besoin

# 3. Importer le schéma et les données avec le compte limité
mysql -h 127.0.0.1 -P 3307 -u stages_deploiement -p tp9_deploiement_test < TP3/001_schema.sql
mysql -h 127.0.0.1 -P 3307 -u stages_deploiement -p tp9_deploiement_test < TP3/002_seed.sql
mysql -h 127.0.0.1 -P 3307 -u stages_deploiement -p tp9_deploiement_test < TP6/sql/01_complements.sql

# 4. Démarrer l'application et vérifier la santé
php -S 127.0.0.1:8002 -t TP7/public TP7/public/router.php
curl -i http://127.0.0.1:8002/api/sante

# 5. Vérifier la configuration
php TP9/scripts/verifier_deploiement.php
```
