# TP06 - API des demandes

Projet informatique 2 · L3 SIL · création, lecture et validation d'une demande avec contrôles.

## À remettre

- Le code de l'API (ce dossier).
- Le tableau des six cas testés avec les sorties : [`preuves/cas_testes.md`](preuves/cas_testes.md) et `tests/requetes.http`.

## Arborescence

```
TP06/
├── public/index.php                 routeur + point d'entrée unique
├── src/
│   ├── Controllers/                 HTTP uniquement (Auth, Demande)
│   ├── Services/                    règles métier : transitions, vérification du mot de passe
│   ├── Repositories/                seul endroit où du SQL est écrit, requêtes préparées
│   ├── Exceptions/HttpException     code HTTP + message neutre
│   └── Support/http.php             réponses JSON et lecture du corps
├── config/                          .env + PDO (hérités du TP04)
├── sql/01_complements.sql           tables utilisateur / historique + comptes fictifs
├── tests/                           tests transitions, hachage, requêtes HTTP
├── docs/contrat-api.md              contrat des routes et table des transitions
├── preuves/cas_testes.md            six cas testés avec sorties
├── .env.example / .gitignore        secrets non versionnés
└── README.md
```

## Installation

```bash
cp .env.example .env
# base du TP03 puis compléments TP06
mysql -u root -p stages < sql/01_complements.sql

# tests sans base (fonctionnent toujours)
php tests/test_transitions.php
php tests/test_hachage.php

# API
php -S localhost:8000 -t public
```

Comptes fictifs : `koffi.adjovi@univ.test` / `Etudiant123!`, `sylvie.hounkpe@univ.test` / `Responsable123!`, `jean.akpo@univ.test` / `Secretariat123!`.

## Réponse aux cinq points de l'énoncé

1. **`DemandeRepository`** : `findById`, `findByStudent`, `findAll`, `create`, `updateStatus`, `historiser` — tous en requêtes préparées.
2. **Table des transitions** : dans `DemandeService::TRANSITIONS` ; toute transition interdite lève `HttpException(409, 'Transition interdite')` avant toute écriture.
3. **Connexion** : `password_hash()` à l'insertion, `password_verify()` à la connexion, `session_regenerate_id(true)` juste après (protection contre la fixation de session). Message d'erreur identique pour compte inconnu et mot de passe faux.
4. **Transaction** : `DemandeRepository::transaction()` fait `beginTransaction` → `UPDATE` statut → `INSERT` historique → `commit`, avec `rollBack()` sur toute exception (tout ou rien).
5. **Tests** : `tests/requetes.http` contient les sept requêtes couvrant succès, 400, 401, 403, 404 et 409 ; les sorties sont consignées dans `preuves/cas_testes.md`.

## Couches respectées

`Controller` (HTTP) → `Service` (règles) → `Repository` (SQL). Le contrôleur ne contient ni SQL ni règle de transition ; le service ne contient ni SQL ni `header()` ; le dépôt ne connaît ni HTTP ni session.

## Sécurité

`.env` est exclu par `.gitignore` ; seules des valeurs fictives sont versionnées. Les erreurs PDO sont journalisées côté serveur et jamais renvoyées au client.
