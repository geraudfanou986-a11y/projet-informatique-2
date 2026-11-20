# TP6 - Tableau des six cas testés avec sorties

Serveur : `php -S 127.0.0.1:8000 -t public` · Base de test dédiée `tp6_corrige_test` (TP3 + `sql/01_complements.sql`).

| n° | Scénario | Requête | Code obtenu | Sortie obtenue |
|---|---|---|---|---|
| 1 | Succès | `POST /api/connexion` (responsable) | `200` | `{"connecte":true,"role":"responsable"}` |
| 2 | Succès | `PATCH /api/demandes/1/statut` `{"statut":"VALIDEE"}` | `200` | `{"id":1,"statut":"VALIDEE"}` |
| 3 | Données invalides | `PATCH .../statut` `{"statut":"REFUSEE"}` | `400` | `{"erreur":"Motif obligatoire pour un refus"}` |
| 4 | Session absente | `GET /api/demandes` sans cookie | `401` | `{"erreur":"Non authentifie"}` |
| 5 | Rôle insuffisant | `PATCH .../statut` avec le compte étudiant | `403` | `{"erreur":"Acces interdit"}` |
| 6 | Ressource absente | `PATCH /api/demandes/999/statut` | `404` | `{"erreur":"Demande introuvable"}` |
| 7 | Transition interdite | `VALIDEE -> REFUSEE` avec motif | `409` | `{"erreur":"Transition interdite"}` |

Le cours demande six cas (succès, 400, 401, 403, 404, 409) : le succès est détaillé en deux requêtes (connexion, puis validation), soit sept appels. La sortie brute est dans [`sorties_http.txt`](sorties_http.txt), les requêtes dans `tests/requetes.http`.

## Vérification en base après le succès

```
id  statut   motif_refus
1   VALIDEE  NULL

demande_id  statut_avant  statut_apres
1           SOUMISE       VALIDEE
```

Le `UPDATE` et l'`INSERT` dans `historique` ont bien été exécutés dans la même transaction.

## Preuve du rollback

La table `historique` a été temporairement masquée (`RENAME TABLE`) dans la base de test, puis la demande 3 (`BROUILLON`) a reçu `PATCH {"statut":"SOUMISE"}` avec le compte responsable :

```
=== Appel avec table historique absente
{"erreur":"Erreur serveur"}
HTTP_CODE=500
=== Apres echec (rollback attendu)
id  statut
3   BROUILLON
=== Table historique restauree
nb_historique
1
```

L'échec de l'INSERT a bien annulé le UPDATE : le statut est resté `BROUILLON`. Sortie complète : [`sorties_rollback.txt`](sorties_rollback.txt).

## Tests sans base (exécutés localement, PHP 8.5)

```
$ php tests/test_transitions.php
OK   BROUILLON  -> SOUMISE   : true  (attendu true)
OK   SOUMISE    -> VALIDEE   : true  (attendu true)
OK   SOUMISE    -> REFUSEE   : true  (attendu true)
OK   REFUSEE    -> BROUILLON : true  (attendu true)
OK   VALIDEE    -> REFUSEE   : false (attendu false)
OK   VALIDEE    -> SOUMISE   : false (attendu false)
OK   REFUSEE    -> VALIDEE   : false (attendu false)

$ php tests/test_hachage.php
OK   Etudiant123!       : password_verify = true
OK   Responsable123!    : password_verify = true
OK   Secretariat123!    : password_verify = true
OK   mot de passe erronne : password_verify = false
```

Tous les fichiers PHP du dossier passent `php -l` sans erreur de syntaxe.
