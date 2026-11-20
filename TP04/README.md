# TP04 - Squelette technique et contrat d'API

Projet informatique 2 · L3 SIL · application Web de gestion des stages (données fictives).

## Prérequis

- PHP 8 ou supérieur (PDO et l'extension `pdo_mysql` activées)
- MySQL ou MariaDB
- Git
- Un navigateur ou `curl` pour appeler l'API

Vérifier rapidement : `php -v` et `php -m | findstr pdo_mysql`.

## Arborescence

```
TP04/
├── public/                  point d'entrée unique (index.php)
│   └── index.php            routage minimal + GET /api/sante
├── src/
│   ├── Controllers/         lecture requête / écriture réponse (HTTP uniquement)
│   ├── Services/            règles métier (aucun SQL, aucun HTTP)
│   └── Repositories/        seule couche qui écrit du SQL, avec requêtes préparées
├── config/                  config.php (.env) et database.php (PDO)
├── tests/                   test de connexion
├── docs/                    contrat d'API et ADR-001
├── .env.example             valeurs factices, versionné
├── .gitignore               exclut .env et les secrets
└── README.md                ce fichier
```

## Installation

```bash
# 1. Configuration locale (le fichier .env n'est jamais versionné)
cp .env.example .env
# éditer .env : DB_NAME=stages, DB_USER=root, DB_PASS=...

# 2. Base de données (scripts du TP03)
mysql -u root -p -e "CREATE DATABASE stages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p stages < ../TP03/001-schema.sql
mysql -u root -p stages < ../TP03/002-seed.sql

# 3. Test de connexion PDO
php tests/test_connexion.php

# 4. Serveur local et route de santé
php -S localhost:8000 -t public
curl -i http://localhost:8000/api/sante
```

Sortie attendue de la route de santé :

```
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8

{"statut":"ok","base":"joignable"}
```

Si la base est injoignable, la route répond `500` avec un message neutre ; le détail technique est écrit dans `error_log`, jamais renvoyé au client. Les deux réponses réellement obtenues (200 et 500) sont conservées dans [`preuves/sorties_sante.txt`](preuves/sorties_sante.txt).

## Contrat des routes

Voir [`docs/contrat-api.md`](docs/contrat-api.md). Le contrat est écrit avant le code et partagé avec le frontend.

## Décision d'architecture

Voir [`docs/ADR-001.md`](docs/ADR-001.md) : monolithe modulaire PHP 8 + PDO, sans framework.

## Document de remise

Les trois éléments à remettre (squelette, contrat des routes, ADR-001) sont aussi réunis dans un seul document, comme dans le rendu de référence :

- `squelette_contrat_adr.docx`
- `squelette_contrat_adr.html`
- `squelette_contrat_adr.pdf`

## Règles respectées

- Une couche, une responsabilité : le SQL reste dans `src/Repositories`.
- Les secrets vivent dans `.env`, exclu par `.gitignore` ; seul `.env.example` est versionné.
- PDO est configuré en mode exception et utilise des requêtes préparées.
- `main` reste exécutable ; la route `/api/sante` prouve que le socle fonctionne.
