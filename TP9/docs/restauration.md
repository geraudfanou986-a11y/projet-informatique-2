# TP9 — Sauvegarde et restauration

Objectif : exporter la base, **supprimer une table sur une base de test**, restaurer,
puis comparer les `COUNT(*)` avant et après. Aucune base de production n'a été touchée.

## 1. Sauvegarde

Commande de référence (le compte est limité, d'où les deux options expliquées dans
`docs/incident.md`) :

```bash
mysqldump -h 127.0.0.1 -P 3307 -u stages_deploiement -pDeploiement123! \
  --single-transaction --skip-add-locks \
  --databases tp9_deploiement_test --add-drop-database --routines --triggers \
  > tp9_deploiement_test.sql
```

Résultat observé : `exit=0`, fichier de **17 254 octets**, aucun avertissement.
L'en-tête du dump indique `Database: tp9_deploiement_test` et contient bien les
`CREATE TABLE` / `INSERT` des 7 tables.

## 2. État avant incident

Script `verifier_deploiement.php` exécuté sur `tp9_deploiement_test`
(`preuves/avant.json`) :

| Table | `COUNT(*)` |
|---|---:|
| `demande_stage` | 4 |
| `encadreur` | 2 |
| `entreprise` | 2 |
| `etudiant` | 3 |
| `historique` | 0 |
| `soutenance` | 1 |
| `utilisateur` | 3 |

## 3. Destruction d'une table (base de test)

```sql
DROP TABLE soutenance;
SHOW TABLES;
```

Tables restantes observées : `demande_stage`, `encadreur`, `entreprise`, `etudiant`,
`historique`, `utilisateur` — la table `soutenance` a bien disparu.

## 4. Restauration

La base de destination `tp9_restauration_test` a été créée avec les mêmes privilèges
que la base de déploiement. Comme l'option `--databases` écrit le nom d'origine dans le
dump (`DROP DATABASE`/`CREATE DATABASE`/`USE`), le nom de base a été remplacé dans une
copie du fichier avant réimport :

```bash
# copie de travail, avec le nom de la base de restauration
(Get-Content -Raw tp9_deploiement_test.sql).Replace(
  'tp9_deploiement_test', 'tp9_restauration_test') | Set-Content _restore.sql

mysql -h 127.0.0.1 -P 3307 -u stages_deploiement -pDeploiement123! \
  --execute="SOURCE _restore.sql"
```

Résultat observé : `exit=0`, aucune erreur. Le contrôle du fichier confirme les
lignes `CREATE DATABASE ... tp9_restauration_test` et `USE \`tp9_restauration_test\``.

> Remarque : le premier réimport, effectué avec le dump non modifié, a aussi recréé
> `tp9_deploiement_test` dans son état complet. Les deux bases de test sont donc
> restaurées ; seule `tp9_restauration_test` sert de référence à la comparaison.

## 5. Comparaison des `COUNT(*)`

`preuves/avant.json` et `preuves/apres.json` :

| Table | Avant | Après | Égalité |
|---|---:|---:|:---:|
| `demande_stage` | 4 | 4 | ✅ |
| `encadreur` | 2 | 2 | ✅ |
| `entreprise` | 2 | 2 | ✅ |
| `etudiant` | 3 | 3 | ✅ |
| `historique` | 0 | 0 | ✅ |
| `soutenance` | 1 | 1 | ✅ |
| `utilisateur` | 3 | 3 | ✅ |

**7 tables sur 7 identiques** : la table supprimée est revenue avec sa ligne, et
aucune autre donnée n'a été perdue.

## 6. Nettoyage

Les deux bases de test (`tp9_deploiement_test`, `tp9_restauration_test`) et le compte
`stages_deploiement` peuvent être supprimés après la soutenance ; les bases de travail
du poste principal (`tp7_corrige_test`, `edudoc`, etc.) n'ont pas été modifiées.
