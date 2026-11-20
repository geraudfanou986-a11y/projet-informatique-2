# Liste de contrôle du dossier de livraison (8 éléments)

La liste reprend les huit éléments énumérés en section 10.1 du cours. Un élément n'est
coché que si le fichier existe **et** si sa preuve de vérification est disponible.

| # | Élément (cours 10.1) | Emplacement dans le dépôt | Preuve de vérification | État |
|---:|---|---|---|---|
| 1 | Dépôt Git | Racine du dépôt, branche `main` | `TP10/preuves/git_log.txt`, `origin/main` = `f2b6366` | ✅ |
| 2 | Fiche de cadrage | `TD1/` (PDF, 2 pages) | Relecture par un autre groupe (rapport dans `TP08/docs/revue_code.md`) | ✅ |
| 3 | Exigences et matrice de traçabilité | `TD2/` (EF, ENF, matrice) | Un test relié à chaque exigence Must dans `TP08/docs/matrice_tests.md` | ✅ |
| 4 | Diagrammes UML | `TP03/` (cas d'utilisation, classes) | Fichiers `.docx`, `.html`, `.pdf` et modèle source | ✅ |
| 5 | Schéma SQL et migrations | `TP03/001_schema.sql`, `TP03/002_seed.sql`, `TP06/sql/01_complements.sql` | `COUNT(*)` du jeu d'essai : 3 étudiants, 2 entreprises, 2 encadreurs, 4 demandes, 1 soutenance | ✅ |
| 6 | Plan de tests et fiche de recette | `TP08/docs/matrice_tests.md`, `TP08/preuves/resultats_tests.*` | Matrice exécutée 32/32, quatre cas limites, `403`, hachage bcrypt | ✅ |
| 7 | README d'installation | `README.md` (racine) + `TP09/README.md`, `TP09/docs/deploiement.md` | Installation sur un second poste simulé, `/api/sante` = 200 | ✅ |
| 8 | Décisions ADR | `TP04/` — ADR-001 | Décision écrite avec contexte, choix et conséquences | ✅ |

**Total : 8 / 8 éléments présents.**

## Vérifications transversales

| Contrôle | Résultat |
|---|---|
| Le numéro de groupe n'a pas été réintroduit dans le README racine | ✅ (`543ac04` l'avait retiré) |
| Données fictives uniquement (`@univ.test`) | ✅ |
| `main` exécutable : `/api/sante` répond 200 | ✅ |
| Aucun mot de passe en clair en base (hash `$2y$`) | ✅ (`TP08/docs/securite.md`) |
| `.env` non versionné (ignoré par Git) | ✅ (`TP06/.gitignore`) |
| Sauvegarde restaurée et comparée | ✅ (`TP09/docs/restauration.md`) |

## Avant de déposer le dossier

- [ ] Ouvrir chaque chemin de la table et vérifier qu'il est lisible sans fichier local manquant.
- [ ] Vérifier que `git status` ne contient aucun `.env`, mot de passe ou donnée réelle.
- [ ] Emporter les preuves de la démonstration (solution de repli, `docs/repetition.md`).
