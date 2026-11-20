# TP07 — Parcours des deux rôles

Dessinés avant le codage, comme demandé dans le chapitre 7, puis adaptés à l'interface finale. Le schéma visuel est dans [`parcours.svg`](parcours.svg) et une version HTML reprenant la charte finale est dans [`public/parcours.html`](../public/parcours.html).

## Parcours étudiant

```
Connexion → Tableau de bord → Nouvelle demande → Soumettre → Détail / Historique
```

| Étape dans l'interface | Action utilisateur | Appel / résultat |
|---|---|---|
| 1. `index.html` | Créer un compte (onglet « Inscription ») ou saisir ses identifiants | `POST /api/inscription` (201) ou `POST /api/connexion` (200) → ouverture de `app.html` |
| 2. `app.html` (tableau de bord) | Lecture des compteurs et des cartes | `GET /api/demandes/mes`, `/api/entreprises`, `/api/etudiants`, `/api/soutenances` |
| 3. `app.html` (modale « Nouvelle demande ») | Choisir l'entreprise dans la liste déroulante | `POST /api/demandes` → `201`, statut `BROUILLON`, rechargement |
| 4. `app.html` (carte) | Cliquer sur « Soumettre » | `PATCH /api/demandes/{id}/statut` → `SOUMISE` |
| 5. `app.html` (modales « Détail » / « Historique ») | Ouvrir le détail et le journal | `GET /api/demandes/{id}/historique` → transitions avec auteurs |

Les données affichées passent toutes par `textContent` : le test du motif de refus contenant `<b>test</b>` et `<script>alert(1)</script>` est détaillé dans `preuves/test_balises.txt`.

## Parcours responsable

```
Connexion → Toutes les demandes → Décision → Soutenance → Historique
```

| Étape dans l'interface | Action utilisateur | Appel / résultat |
|---|---|---|
| 1. `index.html` | Saisir les identifiants responsable | `POST /api/connexion` → ouverture de `app.html` avec le rôle `responsable` |
| 2. `app.html` (vue « Demandes ») | Recherche, filtres, bouton « Actualiser » | `GET /api/demandes` → tableau complet avec noms et entreprises |
| 3. `app.html` (modale de décision) | « Valider » ou « Refuser » avec motif obligatoire | `PATCH /api/demandes/{id}/statut` → `200`, relecture immédiate et notification |
| 4. `app.html` (modale « Soutenance ») | Programmer date et salle | `PATCH /api/demandes/{id}/soutenance` → agenda mis à jour |
| 5. `app.html` (modale « Historique ») | Consulter le journal | `GET /api/demandes/{id}/historique` → statuts, motifs, auteurs et horodatages |

La transition suit l'ordre métier : `BROUILLON → SOUMISE → VALIDEE`. Un refus sans motif renvoie `400` et le message est affiché dans une notification lisible.

## Choix d'interface

- Une coque commune (`app.html`) et des vues clairement titrées, au lieu d'une page séparée par rôle : moins d'allers-retours, un rendu homogène.
- Un bouton « Actualiser » explicite, complété par une relecture automatique (15 s) qui ne s'exécute ni pendant une modale ni pendant une saisie : les données affichées reviennent toujours de la base.
- Aucun lien direct vers les routes API : tout passe par `fetch` dans `public/assets/js/api.js`.
