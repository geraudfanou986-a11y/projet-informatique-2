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

## Branches

`main` reste toujours exécutable. Le travail se fait sur des branches courtes (`tp3`, `tp4`, …)
fusionnées après relecture du diff. Les livrables actuellement présents sont intégrés sur `main`.

| Branche | Contenu |
|---|---|
| `main` | Squelette du dépôt et livrables `TD1`, `TD2`, `TP3`, `TP4`, `TP5`, `TP6`, `TP7` |

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
| TP8 à TP10 | Plan de tests, audit de sécurité, synthèse | À venir |

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
