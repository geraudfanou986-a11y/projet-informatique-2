# TP7 — Parcours des deux rôles

Dessinés avant le codage, comme demandé dans le chapitre 7. Le schéma visuel est dans [`parcours.svg`](parcours.svg).

## Parcours étudiant (4 écrans)

```
Connexion → Mes demandes → Formulaire de dépôt → Détail d'une demande
```

| Écran | Action utilisateur | Appel / résultat |
|---|---|---|
| 1. `connexion.html` | Saisir email + mot de passe | `POST /api/connexion` → redirection vers `etudiant.html` |
| 2. `etudiant.html` (partie liste) | Arrivée sur la page | `GET /api/demandes/mes` → tableau des demandes |
| 3. `etudiant.html` (formulaire) | Saisir l'identifiant entreprise, envoyer | `POST /api/demandes` → `201`, puis rechargement de la liste |
| 4. `etudiant.html` (détail) | Cliquer sur « Voir détail » | Affichage de l'id, de l'entreprise, du statut et du motif avec `textContent` |

Zone transverse : le formulaire de vérification du rendu sécurisé permet de saisir `<b>test</b>` et de constater que les balises s'affichent telles quelles.

## Parcours responsable (3 écrans)

```
Connexion → Liste des demandes à traiter → Décision (soumettre / valider / refuser)
```

| Écran | Action utilisateur | Appel / résultat |
|---|---|---|
| 1. `connexion.html` | Saisir les identifiants responsable | `POST /api/connexion` → redirection vers `responsable.html` |
| 2. `responsable.html` (liste) | Arrivée / clic sur « Rafraîchir » | `GET /api/demandes` → tableau reçu de la base |
| 3. `responsable.html` (décision) | Choisir un statut, saisir un motif si refus, enregistrer | `PATCH /api/demandes/{id}/statut` → `200`, puis relecture de la liste |

## Choix d'interface

- Une seule page par rôle, avec des sections correspondant aux écrans du parcours : moins d'allers-retours, mais des sections clairement titrées.
- Un bouton « Rafraîchir » explicite : il prouve que les données affichées reviennent de la base après l'écriture.
- Aucun lien direct vers les routes API : tout passe par `fetch` dans `public/js/api.js`.
