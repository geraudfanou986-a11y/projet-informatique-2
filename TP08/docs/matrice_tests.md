# TP08 — Matrice de tests

Matrice **exécutée**, pas théorique : le script `scripts/tests_tp8.php` envoie de
vraies requêtes HTTP à l'application lancée (interface TP07 + API TP06 sur
`http://127.0.0.1:8001`), avec une session par rôle (cookies `curl`), puis écrit
le résultat dans `preuves/resultats_tests.txt` et `preuves/resultats_tests.json`.

## Règle du jeu : l'attendu est écrit avant

Dans le script, chaque cas est déclaré **avant** l'exécution, sous la forme
`verifier(cas, exigence, données, attendu, observé, conforme)`. La valeur
`attendu` est une chaîne littérale écrite par l'auteur du test ; la valeur
`observé` est le résultat réel de la requête (code HTTP + corps JSON). Aucun
« attendu » n'est déduit après coup : c'est ce qui rend la matrice vérifiable.

## Comment rejouer

```bash
# Serveur (déjà lancé pour la démonstration)
php -S 127.0.0.1:8001 -t TP07/public TP07/public/router.php

# Matrice complète + écriture des preuves
php TP08/scripts/tests_tp8.php
```

Variables reconnues : `TP8_BASE_URL` (défaut `http://127.0.0.1:8001`),
`TP8_DB_PORT` (défaut `3307`), `TP8_DB_NAME` (défaut `tp7_corrige_test`),
`TP8_DB_USER`, `TP8_DB_PASS`. Le script crée ses propres comptes et demandes de
test puis **les supprime** à la fin.

Résultat de la dernière exécution : **32 / 32 cas conformes**.

## Matrice

La colonne **Preuve** renvoie à la ligne du fichier `preuves/resultats_tests.txt`
(cas n° 1 = ligne 11, cas n° *n* = ligne 10 + *n*).

| # | Cas | Exigence | Données | Attendu | Observé | Conforme | Preuve |
|---|-----|----------|---------|---------|---------|----------|--------|
| 1 | Route de santé joignable | ENF-01 disponibilité | `GET /api/sante` | 200 et base joignable | HTTP 200 `{"statut":"ok","base":"joignable"}` | OUI | L11 |
| 2 | Inscription de l'étudiant A | EF-01 inscription | `POST /api/inscription` (A) | 201 | HTTP 201, compte créé, jeton CSRF renvoyé | OUI | L12 |
| 3 | Inscription de l'étudiant B | EF-01 inscription | `POST /api/inscription` (B) | 201 | HTTP 201, compte créé | OUI | L13 |
| 4 | Inscription avec un email déjà utilisé | EF-01 unicité | `POST /api/inscription` (email de A) | 409 | HTTP 409 `{"erreur":"Un compte existe déjà…"}` | OUI | L14 |
| 5 | Connexion avec un mot de passe faux | ENF-02 authentification | `POST /api/connexion` (A / mauvais) | 401 sans indiquer si le compte existe | HTTP 401 `{"erreur":"Identifiants invalides"}` | OUI | L15 |
| 6 | Connexion du responsable | ENF-02 authentification | `POST /api/connexion` (compte fictif) | 200 | HTTP 200, rôle `responsable` | OUI | L16 |
| 7 | Jeton CSRF fourni par la session | ENF-04 anti-CSRF | `GET /api/csrf` | 200 avec un jeton non vide | jeton de 64 caractères | OUI | L17 |
| 8 | Profil de la session connectée | EF-01 profil | `GET /api/moi` (A) | 200 avec email et jeton CSRF | HTTP 200, email de A + jeton | OUI | L18 |
| 9 | Dépôt d'une demande par l'étudiant A | EF-02 dépôt | `POST /api/demandes {entrepriseId:1}` | 201 avec statut BROUILLON | HTTP 201 `{"id":13,"statut":"BROUILLON"}` | OUI | L19 |
| 10 | Écriture sans en-tête `X-CSRF-Token` | ENF-04 anti-CSRF | `PATCH /api/demandes/13/statut` sans jeton | 403 et aucune modification | HTTP 403 `{"erreur":"Jeton de sécurité absent ou invalide"}` | OUI | L20 |
| 11 | Écriture avec un jeton CSRF invalide | ENF-04 anti-CSRF | `PATCH …` jeton `jeton-bidon` | 403 et aucune modification | HTTP 403 (même message) | OUI | L21 |
| 12 | Soumission de la demande (jeton valide) | EF-03 transition | `PATCH … {SOUMISE}` | 200 et statut SOUMISE | HTTP 200 `{"id":13,"statut":"SOUMISE"}` | OUI | L22 |
| 13 | Déconnexion sans jeton CSRF | ENF-04 anti-CSRF | `POST /api/deconnexion` sans jeton | 403 | HTTP 403 | OUI | L23 |
| 14 | **T-01 Demande inexistante** | EF-03 cas limite | `PATCH /api/demandes/999999/statut {VALIDEE}` | 404 | HTTP 404 `{"erreur":"Demande introuvable"}` | OUI | L24 |
| 15 | **T-02 Refus sans motif** | EF-03 motif obligatoire | `PATCH …/13/statut {REFUSEE}` (motif vide) | 400 | HTTP 400 `{"erreur":"Motif obligatoire pour un refus"}` | OUI | L25 |
| 16 | Validation de la demande | EF-03 transition | `PATCH …/13/statut {VALIDEE}` | 200 et statut VALIDEE | HTTP 200 `{"id":13,"statut":"VALIDEE"}` | OUI | L26 |
| 17 | **T-03 Validation répétée** | EF-03 cas limite | `PATCH …/13/statut {VALIDEE}` une 2ᵉ fois | 409 et statut inchangé (VALIDEE) | HTTP 409 + statut en base VALIDEE | OUI | L27 |
| 18 | **T-04 Date de soutenance invalide** | EF-05 cas limite | `PATCH …/13/soutenance {2026-02-30 10:00}` | 400 (date impossible au calendrier) | HTTP 400 `{"erreur":"Date de soutenance invalide"}` | OUI | L28 |
| 19 | Soutenance valide programmée | EF-05 programmation | `PATCH …/13/soutenance {2026-12-11 10:00}` | 200 avec date enregistrée | HTTP 200 `{"date_heure":"2026-12-11 10:00:00","salle":"B12"}` | OUI | L29 |
| 20 | Soutenance refusée sur une demande non validée | EF-05 règle métier | `PATCH …/14/soutenance` (demande BROUILLON) | 409 | HTTP 409 `{"erreur":"Seule une demande validée…"}` | OUI | L30 |
| 21 | **Étudiant B lit le dossier de l'étudiant A** | ENF-03 autorisation | `GET /api/demandes/13` (session B) | 403 sans aucune donnée | HTTP 403, corps sans `id` ni données | OUI | L31 |
| 22 | Étudiant A lit sa propre demande | ENF-03 autorisation | `GET /api/demandes/13` (session A) | 200 | HTTP 200, demande de A | OUI | L32 |
| 23 | Le responsable lit n'importe quelle demande | ENF-03 autorisation | `GET /api/demandes/13` (responsable) | 200 | HTTP 200 | OUI | L33 |
| 24 | Étudiant sur la liste du personnel | ENF-03 autorisation | `GET /api/demandes` (session A) | 403 | HTTP 403 `{"erreur":"Acces interdit"}` | OUI | L34 |
| 25 | Accès sans session | ENF-02 authentification | `GET /api/demandes` sans cookie | 401 | HTTP 401 `{"erreur":"Non authentifie"}` | OUI | L35 |
| 26 | La liste « mes demandes » de B ne contient pas celle de A | ENF-03 cloisonnement | `GET /api/demandes/mes` (session B) | 200 sans la demande de A | demande A présente : non | OUI | L36 |
| 27 | **Injection `1 OR 1=1` dans l'URL** | ENF-05 injection SQL | `GET /api/demandes/1 OR 1=1` | 404 (aucune route, aucune fuite) | HTTP 404 `{"erreur":"Route inconnue"}` | OUI | L37 |
| 28 | **Injection `1 OR 1=1` dans le corps JSON** | ENF-05 injection SQL | `POST /api/demandes {entrepriseId:"1 OR 1=1"}` | 400, aucune ligne créée | HTTP 400 + demandes avant/après : 2/2 | OUI | L38 |
| 29 | Dépôt avec une entreprise inexistante | EF-02 validation | `POST /api/demandes {entrepriseId:999999}` | 400 | HTTP 400 `{"erreur":"Entreprise introuvable"}` | OUI | L39 |
| 30 | Route inconnue | ENF-06 robustesse | `GET /api/inconnu` | 404 | HTTP 404 `{"erreur":"Route inconnue"}` | OUI | L40 |
| 31 | Détail d'une demande inexistante | EF-03 cas limite | `GET /api/demandes/999999` | 404 | HTTP 404 `{"erreur":"Demande introuvable"}` | OUI | L41 |
| 32 | Mot de passe stocké haché | ENF-06 confidentialité | `SELECT hash FROM utilisateur WHERE email = …` | hash bcrypt (`$2y$`), `password_verify()` vrai, jamais en clair | algorithme `$2y$`, longueur 60, identique au mot de passe : non | OUI | L42 |

Les cas **T-01 à T-04** demandés par le TP08 sont en gras ; les autres lignes
couvrent l'inscription, l'authentification, le CSRF, les autorisations,
l'injection SQL et le hachage exigés par le même TP.

## Ce que la matrice a révélé (et fait corriger)

La première exécution a donné **30/32** : les cas n° 21 et 22 échouaient. Le test
d'autorisation a mis au jour un vrai défaut de code : la création d'une demande et
la liste « mes demandes » utilisaient l'identifiant du **compte** (`utilisateur.id`)
au lieu de l'identifiant de la **fiche étudiant** (`etudiant.id`). Résultat concret :
l'étudiant B voyait la demande de l'étudiant A, et A ne voyait pas la sienne.

Correction apportée dans `TP06/src/Controllers/DemandeController.php` :
`creer()` et `listerMesDemandes()` utilisent désormais `$u['etudiant_id']` et
refusent par un `403` un compte étudiant sans fiche liée. Après correction, la
matrice repasse à **32/32**. C'est l'intérêt d'un test exécuté : il a trouvé un bug
que la relecture seule n'avait pas vu.
