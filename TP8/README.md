# TP8 — Plan de tests et audit de sécurité

Vérification réelle du projet : une **matrice de tests exécutée**, quatre **cas
limites**, des contrôles d'**autorisation** et d'**injection**, la vérification du
**hachage** des mots de passe et la description de la protection **CSRF**. Données
fictives uniquement.

Ce TP s'appuie sur l'application des TP6 (API) et TP7 (interface), servie sur un
seul port.

## Contenu

| Fichier | Rôle |
|---|---|
| `scripts/tests_tp8.php` | Exécute la matrice (requêtes HTTP réelles, sessions par rôle) et écrit les preuves |
| `docs/matrice_tests.md` | Matrice complète (32 lignes) : cas, exigence, données, attendu, observé, preuve |
| `docs/cas_limites.md` | Les quatre cas limites exigés, analysés un par un |
| `docs/securite.md` | Hachage des mots de passe + protection CSRF retenue |
| `docs/revue_code.md` | Revue du dépôt d'un autre groupe : quatre remarques classées |
| `preuves/resultats_tests.txt` | Matrice renseignée (sortie brute du script) |
| `preuves/resultats_tests.json` | Même résultat, exploitable par un programme |

## Prérequis

- PHP 8 avec `curl` et `pdo_mysql`, MariaDB ou MySQL.
- L'application TP6 + TP7 et sa base (voir `TP7/README.md`).

## Lancer les tests

```bash
# 1. Le serveur de l'application (interface TP7 + API TP6 sur un seul port)
php -S 127.0.0.1:8001 -t TP7/public TP7/public/router.php

# 2. La matrice de tests (dans un autre terminal)
php TP8/scripts/tests_tp8.php
```

Le script :

1. vérifie que le serveur répond (`GET /api/sante`) ;
2. crée deux étudiants de test (A et B) et ouvre une session pour chacun, plus une
   session « responsable » avec un compte fictif ;
3. exécute les 32 cas de la matrice, **attendu déclaré avant** chaque requête ;
4. lit directement la base pour vérifier le hachage du mot de passe ;
5. **supprime** ses comptes et demandes de test ;
6. écrit `preuves/resultats_tests.txt` et `.json`, et renvoie le code de sortie `0`
   seulement si tous les cas sont conformes.

Variables reconnues : `TP8_BASE_URL` (défaut `http://127.0.0.1:8001`),
`TP8_DB_PORT` (défaut `3307`), `TP8_DB_NAME` (défaut `tp7_corrige_test`),
`TP8_DB_USER`, `TP8_DB_PASS`.

## Résultat

Dernière exécution : **32 / 32 cas conformes** (voir `preuves/resultats_tests.txt`).

La première exécution avait donné 30/32 : les deux cas d'autorisation inter-étudiants
échouaient et ont révélé un vrai défaut (identifiant de compte utilisé à la place de
l'identifiant de fiche étudiant), corrigé dans `TP6/src/Controllers/DemandeController.php`.
Détail dans `docs/matrice_tests.md`.

## Points de contrôle du TP8

1. **Matrice ≥ 8 lignes, attendu écrit avant** → 32 lignes, `docs/matrice_tests.md` ;
   l'attendu est une chaîne littérale du script, l'observé vient de la réponse HTTP.
2. **Quatre cas limites exécutés** → `docs/cas_limites.md` : demande inexistante
   (`404`), refus sans motif (`400`), validation répétée (`409`), date invalide
   (`400`).
3. **Autorisation** → étudiant B sur le dossier de A : `403` sans donnée ; liste
   « mes demandes » cloisonnée ; **injection** `1 OR 1=1` dans l'URL (`404`) et dans
   le corps JSON (`400`, aucune ligne créée).
4. **Hachage et CSRF** → `docs/securite.md` : bcrypt vérifié en base
   (`$2y$`, `password_verify()` vrai) ; jeton CSRF de 64 caractères en en-tête
   `X-CSRF-Token`, comparé par `hash_equals()`, plus cookie `HttpOnly`/`SameSite`.
5. **Revue de code** → `docs/revue_code.md` : quatre remarques classées sur le dépôt
   `thelabofferdi/pjinfo2`.
