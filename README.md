# Projet informatique 2 — Gestion des stages

Application web de gestion des stages : dépôt des demandes par les étudiants, validation ou refus
motivé, affectation d'un encadreur, consultation de l'état des demandes et programmation des
soutenances (données fictives uniquement).

| | |
|---|---|
| **Cours** | Projet informatique 2 — L3 SIL (10 chapitres, 25 h, 10 TP) |
| **Groupe** | Ange, Vanessa, Géraud |
| **Dépôt** | <https://github.com/geraudfanou986-a11y/projet-informatique-2> |
| **Environnement** | PHP 8, MySQL ou MariaDB, Git — données fictives uniquement |

## Organisation du dépôt

| Dossier | Contenu |
|---|---|
| `TD1/` | Fiche de cadrage du projet (PDF) |
| `TD2/` | Exigences fonctionnelles et non fonctionnelles, matrice de traçabilité (PDF) |
| `TP3/` | Modèles UML (cas d'utilisation, classes), scripts SQL et jeu d'essai vérifié ; document de correction en `.docx`, `.html` et `.pdf` |
| `TP4/` | Squelette technique en couches, contrat d'API, ADR-001, test de la route `/api/sante` et document de remise (`.docx`, `.html`, `.pdf`) |
| `TP5/` | Organisation Git : kanban, conventions de branches, conflit résolu et sortie de `git log --graph` |
| `TP6/` | API réelle des demandes : contrôleurs, services, repositories, authentification et preuves HTTP |
| `TP7/` | Interface accessible reliée à l'API, parcours des rôles, test XSS et scénario de bout en bout |
| `TP8/` | Plan de tests exécuté, quatre cas limites, audit de sécurité (hachage, CSRF) et fiche de revue de code |
| `TP9/` | Déploiement sur un second poste, compte SQL dédié, sauvegarde restaurée et ticket d'incident |
| `TP10/` | Dossier de livraison (liste de contrôle), scénario de démonstration, questions individuelles et rétrospective |

## Branches

`main` reste toujours exécutable. Le travail se fait sur des branches courtes (`tp3`, `tp4`, …)
fusionnées après relecture du diff. Les livrables actuellement présents sont intégrés sur `main`.

| Branche | Contenu |
|---|---|
| `main` | Squelette du dépôt et livrables `TD1`, `TD2`, `TP3` à `TP10` |

## Périmètre retenu (v1)

**Inclus** : authentification et rôles, dépôt d'une demande de stage, validation ou refus motivé,
affectation d'un encadreur, consultation de l'état des demandes, journal des décisions,
programmation d'une soutenance.

**Exclus** (reportés en v2) : paiement en ligne, notifications SMS, portail entreprise,
convention signée électroniquement, messagerie interne.

## État d'avancement

| TP | Livrable | Statut |
|---|---|---|
| TD1 | Fiche de cadrage | Fait |
| TD2 | Exigences (EF, ENF) et matrice de traçabilité | Fait |
| TP3 | Diagrammes UML, schéma SQL, jeu d'essai vérifié | Fait |
| TP4 | Squelette technique, contrat d'API, ADR-001 | Fait |
| TP5 | Dépôt Git et organisation d'équipe | Fait |
| TP6 | API des demandes | Fait |
| TP7 | Interface et intégration | Fait |
| TP8 | Plan de tests et audit de sécurité | Fait |
| TP9 | Déploiement et sauvegarde | Fait |
| TP10 | Dossier de livraison et soutenance | Supports prêts (soutenance orale à tenir) |

## Démarrage rapide

```bash
mysql -u root -p -e "CREATE DATABASE stages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p stages < TP3/001_schema.sql
mysql -u root -p stages < TP3/002_seed.sql
mysql -u root -p stages < TP6/sql/01_complements.sql

# API seule (TP6)
cd TP6 && cp .env.example .env && php -S localhost:8000 -t public
curl -i http://localhost:8000/api/sante

# Interface + API sur un seul port (TP7)
cd TP7 && php -S 127.0.0.1:8001 -t public public/router.php
```

> Les commandes `mysql -u root` ci-dessus ne servent qu'à créer la base initialement.
> L'application ne doit **jamais** utiliser `root` : créer le compte dédié avec
> `TP9/sql/00_utilisateur.sql`, copier `TP9/config/.env.exemple` vers `TP6/.env`, puis
> importer les scripts avec ce compte. Sur l'environnement du cours, le port MariaDB
> est `3307`. Le `TP6/.env` est obligatoire même si l'on ne démarre que TP7 (le routeur
> TP7 délègue `/api/*` à l'API TP6).

## Déploiement et livraison

- **TP9** : procédure complète de déploiement sur un second poste, compte SQL limité,
  sauvegarde restaurée et incident `mysqldump` documenté → `TP9/README.md`.
- **TP10** : liste de contrôle des huit éléments du dossier, démonstration de huit
  minutes, justifications, questions individuelles et rétrospective → `TP10/README.md`.
