# Rapport de test local — Plateforme Complète

Date : 20 novembre 2026. Tests effectués sur le poste de développement avant livraison.

## Environnement de test

| Élément | Valeur utilisée ici |
|---|---|
| Serveur web | `php -S 127.0.0.1:8003 -t . router.php` (port 8003 pour ne pas gêner les serveurs des TP) |
| PHP | 8.5.8, extension `pdo_mysql` active |
| MariaDB | 10.4.32 sur `127.0.0.1:3307` |
| Base de test | `plateforme_complete_test` |
| Compte applicatif | `plateforme_app` (créé par l'installateur, **jamais root**) |

Le professeur peut utiliser exactement les mêmes étapes avec le port 8000 et MariaDB sur 3306.

## 1. Installation

1. `GET /installer.php` → formulaire affiché, prérequis `[OK] PHP >= 8.1`, `[OK] pdo_mysql`.
2. `POST /installer.php` avec les paramètres du tableau ci-dessus → **Installation réussie**, quatre fichiers SQL importés dans l'ordre (`001_schema.sql`, `002_seed.sql`, `003_complements.sql`, `004_piece_jointe.sql`), `.env` écrit à la racine.
3. `POST /installer.php` avec `action=reinstall` → réinstallation propre, mêmes résultats. Le bouton « Réinstaller » ne supprime que la base nommée dans le formulaire.

> Correctif apporté pendant le test : `FLUSH PRIVILEGES` est désormais tenté mais non bloquant (MariaDB 10.4+ applique les `GRANT` immédiatement ; certaines installations ont une table système Aria endommagée).

## 2. Vérification de la base

Commande : `php scripts/verifier.php`

Résultat : **OK — installation conforme.** Les 8 tables sont présentes avec les effectifs attendus : 3 étudiants, 2 entreprises, 2 encadreurs, 4 demandes, 1 soutenance, 3 utilisateurs, 0 historique, table `piece_jointe` présente (contenu libre : elle se remplit pendant la démonstration).

Sortie brute : `verification_installation.txt`.

## 3. API JSON (requêtes HTTP réelles)

| Contrôle | Résultat |
|---|---|
| `GET /api/sante` | `200 {"statut":"ok","base":"joignable"}` |
| `POST /api/connexion` (étudiant) | `200`, rôle `etudiant`, jeton CSRF renvoyé |
| `GET /api/moi`, `/api/demandes/mes`, `/api/entreprises`, `/api/etudiants`, `/api/soutenances` | `200` |
| `POST /api/demandes` | `201 {"id":5,"statut":"BROUILLON"}` |
| `PATCH /api/demandes/5/statut` par l'étudiant | `200 {"id":5,"statut":"SOUMISE"}` |
| `PATCH /api/demandes/5/statut` par le responsable | `200 {"id":5,"statut":"VALIDEE"}` |
| `PATCH /api/demandes/5/soutenance` | `200`, date `2026-12-18 10:30:00`, salle `C204` |
| `GET /api/demandes/5/historique` | 2 lignes : `BROUILLON → SOUMISE` puis `SOUMISE → VALIDEE`, avec les emails des auteurs |
| Étudiant B sur la demande de l'étudiant A | `403` sur le détail **et** sur l'historique |
| `php api/tests/test_transitions.php` | transitions autorisées/interdites conformes |
| `php api/tests/test_hachage.php` | 3 mots de passe bcrypt vérifiés |

## 4. Interface (navigateur)

Parcours effectué dans le navigateur intégré, sans erreur console :

1. Clic sur le compte de démonstration étudiant → connexion → tableau de bord avec compteurs, cartes de demandes, activité et agenda.
2. Clic sur le compte responsable → tableau de bord avec les 5 demandes, noms des étudiants et des entreprises, filtres, recherche.
3. Ouverture de la modale « Programmer une soutenance » et de la modale « Historique des décisions » → dates et auteurs correctement formatés en français.
4. Vue « Agenda des soutenances » → 2 soutenances triées par date.

Captures (prises pendant le scénario, avant la remise à zéro finale de la base). Elles ont été refaites depuis l'interface finale après l'ajout du libellé visible « Se déconnecter » : la présence du libellé et les données attendues ont été contrôlées dans le DOM avant chaque capture :

| Fichier | Contenu |
|---|---|
| `01_connexion.png` | Page d'accueil / connexion et comptes de démonstration |
| `02_tableau_etudiant.png` | Tableau de bord étudiant après dépôt, soumission, validation et soutenance |
| `03_tableau_responsable.png` | Tableau de bord responsable (5 dossiers, compteurs, agenda) |
| `04_liste_demandes_responsable.png` | Liste complète avec noms, statuts, soutenances et actions |
| `05_modale_soutenance.png` | Formulaire de programmation d'une soutenance |
| `06_agenda_soutenances.png` | Agenda trié par date |
| `07_historique_decisions.png` | Journal des transitions avec auteurs et horodatages |
| `08_document_joint.png` | Détail d'une demande avec document joint, aperçu de l'image et libellé « Se déconnecter » visible |

## 5. Non-régression des TP livrés

Les API modifiées de façon additive (nouvelles routes `/api/etudiants`, `/api/soutenances`, `/api/demandes/{id}/historique` et droit donné à l'étudiant de soumettre son propre brouillon) ont été rejouées contre le serveur des TP : **32/32 cas conformes** dans `TP8/scripts/tests_tp8.php`.

La plateforme finale reste autonome : elle contient sa propre copie de l'API dans `api/` et ne dépend d'aucun dossier `TPx` pour fonctionner chez un tiers.

## 6. Vérification complémentaire avant livraison (20/11/2026)

Un second passage, plus large, a été exécuté juste avant la remise : **44 contrôles réussis, 0 échec**. Il couvre en plus :

- la réinstallation complète via `/installer.php` puis `/verifier.php` (`installation conforme`) ;
- les trois rôles (étudiant, responsable, secrétariat), le mauvais mot de passe (`401`) et l'absence de session (`401`) ;
- les lectures autorisées par rôle, les accès interdits (`403`) et la protection CSRF (`403` sans jeton) ;
- le cycle complet : `BROUILLON → SOUMISE → VALIDEE`, refus sans motif (`400`), double soumission et transition interdite (`409`), programmation de soutenance, historique avec auteurs, puis relecture depuis la base ;
- la non-divulgation d'une soutenance à un autre étudiant ;
- `php api/tests/test_transitions.php`, `php api/tests/test_hachage.php` et `TP8/scripts/tests_tp8.php` (**32/32**).

Parcours navigateur refait après ce passage : connexion, création d'une demande, soumission, modales « Détail » et « Historique », vues « Soutenances » et « Historique », recherche et filtres, déconnexion, connexion responsable, refus sans motif puis avec motif XSS, validation, programmation de soutenance. Aucune erreur console.

> **Correction trouvée puis corrigée pendant cette vérification :** le champ `datetime-local` envoyait `2026-12-20T10:00` à l'API, qui n'accepte que `2026-12-20 10:00` (`400 Date de soutenance invalide`). `public/assets/js/app.js` convertit désormais le `T` en espace. Le même fichier a été mis à jour dans `TP7/public/assets/js/app.js`. Test navigateur rejoué : `200`, toast « Soutenance programmée. », date affichée `20 déc. 2026, 10:00`.

> **Rendu sécurisé vérifié dans l'interface finale :** le motif `<script>alert(1)</script> <b>test</b>` saisi chez le responsable est relu chez l'étudiant sous forme de texte littéral (0 balise `<script>` créée, 0 balise `<b>` créée, aucune boîte `alert()`, aucun appel à `innerHTML`). Même résultat sur TP7, avec les captures `TP7/preuves/capture_etudiant_xss.png` et `capture_responsable_persistance.png`.

## 7. Ajout demandé après vérification : déconnexion visible et documents joints

**Déconnexion.** Le bouton de la barre latérale affiche désormais le libellé « Se déconnecter » en toutes lettres (au lieu d'une icône seule), dans la plateforme finale et dans TP7. Test navigateur : clic sur le libellé → retour à `index.html`, puis `GET /api/moi` → `401` : la session est bien fermée.

**Documents joints.** Nouvelle table `piece_jointe` (`sql/004_piece_jointe.sql`), importée par l'installateur et acceptée par `verifier.php` (contenu libre). Les fichiers sont écrits dans `stockage/`, **hors de `public/`**, avec un nom tiré au sort ; la base ne garde que les métadonnées. Types vérifiés sur le contenu avec `finfo` (PDF, JPEG, PNG), limite 2 Mo, jeton CSRF exigé sur écriture, contrôle d'accès réutilisé depuis `DemandeService::detail()`.

Contrôles HTTP réels : **17 réussis, 0 échec** (`test_upload.php`) :

| Contrôle | Résultat |
|---|---|
| Connexion étudiant, création d'une demande | `200`, `201` |
| Envoi d'un PNG, lecteur de la demande → `piece_nom` et `piece_type` | `201`, détails corrects |
| Téléchargement : octets identiques au fichier envoyé, `Content-Type: image/png`, `attachment` + `nosniff` | `200` |
| HTML renommé `faux.png` | `400 Format refusé` |
| Envoi sans jeton CSRF | `403` |
| Remplacement par un PDF, relecture du détail | `201`, `application/pdf` |
| Responsable : lecture autorisée, envoi refusé | `200`, `403` |
| Autre étudiant inscrit : téléchargement de la pièce | `403` |
| Retrait puis nouvelle lecture | `200`, `404` |

Parcours navigateur complété sur `http://127.0.0.1:8003/` : création d'une demande avec fichier joint depuis la modale, indicateur 📎 sur la carte, détail avec ligne « Document joint », aperçu de l'image, lien « Ouvrir / télécharger », boutons « Remplacer » et « Retirer », retrait effectif, déconnexion par le libellé visible. Aucune erreur console (hors le `401` volontairement provoqué pour prouver la déconnexion).

> La réinstallation supprime aussi les anciens documents de `stockage/` (sauf `.gitkeep`) : aucune donnée de test n'est laissée dans le dossier livré. Le contrôle final après réinstallation affiche toujours **OK — installation conforme.** avec 8 tables.
