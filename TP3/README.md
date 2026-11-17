# TP3 - Modèle UML et schéma SQL

Projet informatique 2 · L3 SIL · Fil rouge : gestion des stages et des soutenances (données fictives).

## Contenu du dossier

| Fichier | Rôle |
|---|---|
| `001_schema.sql` | Création des 5 tables (clés primaires, étrangères, UNIQUE) |
| `002_seed.sql` | Jeu d'essai : 3 étudiants, 2 entreprises, 2 encadreurs, 4 demandes, 1 soutenance |
| `003_verification.sql` | Vérification par `COUNT(*)` et test de la contrainte UNIQUE |
| `cas_usage.puml` / `.png` | Diagramme de cas d'utilisation (3 acteurs) |
| `classes.puml` / `.png` | Diagramme de classes avec multiplicités |
| `sequence_valider.puml` / `.png` | Séquence du scénario « Valider une demande » |
| `activite_valider.png` | Diagramme d'activité (branches d'échec 401, 404, 403, 409, 500) |
| `schema_relationnel.png` | Schéma relationnel (PK / FK) |
| `modele_donnees.pdf` | Document de correction complet |

## Installation

Prérequis : MySQL ou MariaDB.

```bash
mysql -u root -p -e "CREATE DATABASE stages CHARACTER SET utf8mb4;"
mysql -u root -p stages < 001_schema.sql
mysql -u root -p stages < 002_seed.sql
mysql -u root -p stages < 003_verification.sql
```

Résultat attendu de `003_verification.sql` : etudiant 3, entreprise 2, encadreur 2, demande_stage 4, soutenance 1.

## Règles métier traduites en base

- Un étudiant a plusieurs demandes : clé étrangère `demande_stage.etudiant_id`.
- Une demande concerne une entreprise : clé étrangère `demande_stage.entreprise_id`.
- Une demande a 0 ou 1 encadreur : `demande_stage.encadreur_id` nullable.
- Une demande a 0 ou 1 soutenance : `soutenance.demande_id NOT NULL UNIQUE`.

## Générer les images depuis les sources PlantUML

```bash
plantuml cas_usage.puml classes.puml sequence_valider.puml
```
