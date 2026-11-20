# Ticket d'incident — sauvegarde impossible avec le compte dédié

| Champ | Valeur |
|---|---|
| **Référence** | TP9-INC-001 |
| **Titre** | `mysqldump` échoue avec `ERROR 1044` sur `LOCK TABLES` avec le compte `stages_deploiement` |
| **Gravité** | Moyenne — bloque la sauvegarde, mais n'affecte pas l'application |
| **Statut** | Résolu, reproductible |
| **Date** | TP9 — déploiement sur le second poste |
| **Environnement** | MariaDB 10.4.32, `127.0.0.1:3307`, compte `stages_deploiement`@`127.0.0.1`, base `tp9_deploiement_test` |

## 1. Symptôme

Commande exécutée avec le compte limité créé pour le TP9 :

```bash
mysqldump -h 127.0.0.1 -P 3307 -u stages_deploiement -pDeploiement123! \
  --databases tp9_deploiement_test --add-drop-database
```

Résultat : **code de sortie 2**, fichier de sortie tronqué à **2 228 octets** (en-tête
seul, aucune table), message (`preuves/incident.txt`) :

```
mysqldump.exe: Got error: 1044: "Access denied for user 'stages_deploiement'@'127.0.0.1'
to database 'tp9_deploiement_test'" when using LOCK TABLES
```

L'application, elle, fonctionnait : `/api/sante` répondait `200`. Le problème venait
donc bien de l'outil de sauvegarde, pas de la configuration PDO.

## 2. Reproduction

1. Créer le compte avec exactement les privilèges de `TP9/sql/00_utilisateur.sql`.
2. Importer les trois scripts SQL dans `tp9_deploiement_test`.
3. Lancer la commande ci-dessus, **sans** `--single-transaction`.
4. Observer le code 2 et le message `when using LOCK TABLES`.

Le message est volontairement trompeur : il dit `Access denied ... to database`, ce qui
laisse croire à un problème de `GRANT` sur la base, alors que le vrai privilège manquant
est `LOCK TABLES`.

## 3. Cause racine

Par défaut, `mysqldump` verrouille les tables pour obtenir un instantané cohérent. Cela
demande le privilège `LOCK TABLES`, qui **n'a pas été accordé** au compte dédié : la liste
`SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER` est volontairement
restrictive (moindre privilège, exigence du TP9 : pas de compte `root`).

Le refus est donc le comportement attendu d'un compte correctement limité ; c'est la
commande de sauvegarde qui était inadaptée.

## 4. Correctif retenu

Utiliser un instantané transactionnel au lieu d'un verrouillage :

```bash
mysqldump -h 127.0.0.1 -P 3307 -u stages_deploiement -pDeploiement123! \
  --single-transaction --skip-add-locks \
  --databases tp9_deploiement_test --add-drop-database --routines --triggers \
  > tp9_deploiement_test.sql
```

Résultat : **code 0**, dump complet de **17 254 octets**, restauration réussie
(voir `docs/restauration.md`).

- `--single-transaction` : cohérence via une transaction InnoDB, sans `LOCK TABLES`.
- `--skip-add-locks` : évite d'écrire des `LOCK TABLES` dans le fichier, qui
  échoueraient aussi à la restauration avec le même compte.

## 5. Correctif écarté

`GRANT LOCK TABLES ON tp9_deploiement_test.* TO 'stages_deploiement'@'127.0.0.1';`
fonctionnerait, mais donnerait au compte un privilège dont l'application n'a pas
besoin. Un autre correctif dangereux serait de remplacer le compte par `root` dans la
commande de sauvegarde : cela annulerait l'exigence « pas de root » du TP9 et exposerait
le mot de passe administrateur dans un script versionné. Les deux ont été rejetés.

## 6. Prévention

- Commande de référence consignée dans `TP9/README.md` et `docs/restauration.md`.
- `TP9/sql/00_utilisateur.sql` commente explicitement l'absence de `LOCK TABLES`.
- `scripts/verifier_deploiement.php` vérifie que le compte reste limité à une seule base.
- Règle d'équipe : aucun `root` dans un `.env`, un script ou une documentation de
  déploiement ; le compte administrateur ne sert qu'à la création initiale du compte.
