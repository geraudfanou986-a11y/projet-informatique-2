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
| `TP03/` | Modèles UML (cas d'utilisation, classes), scripts SQL et jeu d'essai vérifié ; document de correction en `.docx`, `.html` et `.pdf` |
| `TP04/` | Squelette technique en couches, contrat d'API, ADR-001, test de la route `/api/sante` et document de remise (`.docx`, `.html`, `.pdf`) |
| `TP05/` | Organisation Git : kanban, conventions de branches, conflit résolu et sortie de `git log --graph` |
| `TP06/` | API réelle des demandes : contrôleurs, services, repositories, authentification et preuves HTTP |
| `TP07/` | Interface accessible reliée à l'API, parcours des rôles, test XSS et scénario de bout en bout |
| `TP08/` | Plan de tests exécuté, quatre cas limites, audit de sécurité (hachage, CSRF) et fiche de revue de code |
| `TP09/` | Déploiement sur un second poste, compte SQL dédié, sauvegarde restaurée et ticket d'incident |
| `TP10/` | Dossier de livraison (liste de contrôle), scénario de démonstration, questions individuelles et rétrospective |

## Branches

`main` reste toujours exécutable. Le travail se fait sur des branches courtes (`tp3`, `tp4`, …)
fusionnées après relecture du diff. Les livrables actuellement présents sont intégrés sur `main`.

| Branche | Contenu |
|---|---|
| `main` | Squelette du dépôt et livrables `TD1`, `TD2`, `TP03` à `TP10` |

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
| TP03 | Diagrammes UML, schéma SQL, jeu d'essai vérifié | Fait |
| TP04 | Squelette technique, contrat d'API, ADR-001 | Fait |
| TP05 | Dépôt Git et organisation d'équipe | Fait |
| TP06 | API des demandes | Fait |
| TP07 | Interface et intégration | Fait |
| TP08 | Plan de tests et audit de sécurité | Fait |
| TP09 | Déploiement et sauvegarde | Fait |
| TP10 | Dossier de livraison et soutenance | Supports prêts (soutenance orale à tenir) |

## Démarrage rapide

```bash
mysql -u root -p -e "CREATE DATABASE stages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p stages < TP03/001_schema.sql
mysql -u root -p stages < TP03/002_seed.sql
mysql -u root -p stages < TP06/sql/01_complements.sql

# API seule (TP06)
cd TP06 && cp .env.example .env && php -S localhost:8000 -t public
curl -i http://localhost:8000/api/sante

# Interface + API sur un seul port (TP07)
cd TP07 && php -S 127.0.0.1:8001 -t public public/router.php
```

> Les commandes `mysql -u root` ci-dessus ne servent qu'à créer la base initialement.
> L'application ne doit **jamais** utiliser `root` : créer le compte dédié avec
> `TP09/sql/00_utilisateur.sql`, copier `TP09/config/.env.exemple` vers `TP06/.env`, puis
> importer les scripts avec ce compte. Sur l'environnement du cours, le port MariaDB
> est `3307`. Le `TP06/.env` est obligatoire même si l'on ne démarre que TP07 (le routeur
> TP07 délègue `/api/*` à l'API TP06).

## Déploiement et livraison

- **TP09** : procédure complète de déploiement sur un second poste, compte SQL limité,
  sauvegarde restaurée et incident `mysqldump` documenté → `TP09/README.md`.
- **TP10** : liste de contrôle des huit éléments du dossier, démonstration de huit
  minutes, justifications, questions individuelles et rétrospective → `TP10/README.md`.
