# TP7 — Scénario de bout en bout documenté

Objectif : prouver que l'interface est réellement reliée à l'API et à la base, et que le statut
reste après rafraîchissement de la page. Résultats bruts dans [`../preuves/scenario_bout_en_bout.txt`](../preuves/scenario_bout_en_bout.txt).
Version HTML reprenant la charte finale : [`../public/scenario.html`](../public/scenario.html).

| Étape | Rôle | Action dans l'interface | Appel | Résultat attendu |
|---|---|---|---|---|
| 1 | Étudiant | Se connecter depuis l'accueil | `POST /api/connexion` | `200`, ouverture de `app.html` |
| 2 | Étudiant | Ouvrir la vue « Demandes » | `GET /api/demandes/mes` | `200`, cartes remplies |
| 3 | Étudiant | « Nouvelle demande » → choisir l'entreprise | `POST /api/demandes` | `201`, message « Demande créée… », liste rechargée |
| 4 | Étudiant | Cliquer sur « Soumettre » | `PATCH /api/demandes/{id}/statut` | `200`, statut `SOUMISE` |
| 5 | Responsable | Se connecter puis refuser sans motif | `PATCH /api/demandes/{id}/statut` | `400`, message lisible, aucune écriture |
| 6 | Responsable | Valider la demande soumise | `PATCH /api/demandes/{id}/statut` | `200`, liste rechargée immédiatement |
| 7 | Responsable | **Rafraîchir la page** (F5) | `GET /api/demandes` | La demande affiche toujours `VALIDEE` |

## Astuce pour observer les deux rôles

Les onglets d'un même navigateur partagent le cookie `PHPSESSID` : se connecter comme responsable
remplace la session étudiant. Pour suivre les deux rôles en direct, utilisez deux navigateurs
différents ou une fenêtre privée. Sinon, déconnectez-vous simplement entre les deux rôles.
L'actualisation automatique (15 s) relit la base à chaque cycle, sauf pendant une modale ou une saisie.

## Résultat observé

```
Demande n° 5 créée avec le statut BROUILLON.
Demande n° 5 : statut mis à jour en SOUMISE.
Demande n° 5 : statut mis à jour en VALIDEE.
Après F5 : la ligne affiche toujours VALIDEE.
En base  : {"id":5,"etudiant_id":1,"entreprise_id":2,"statut":"VALIDEE","motif_refus":null}
Historique : BROUILLON -> SOUMISE -> VALIDEE
```

Capture de la relecture après F5 : `../preuves/capture_responsable_persistance.png`.

## Pourquoi cette dernière étape compte

Sans le rafraîchissement, le statut pourrait venir uniquement de la mémoire du navigateur.
Après F5, la page refait un appel `GET /api/demandes` : la valeur affichée provient donc de la base.
C'est cette étape qui prouve la persistance.

## Cas d'erreurs joués pendant le scénario

| Code | Déclenchement | Comportement de l'interface |
|---|---|---|
| `400` | Refus sans motif | Message « Motif obligatoire pour un refus » dans une notification |
| `401` | Session absente | Retour à l'accueil avec « Votre session a expiré » |
| `403` | Demande d'un autre étudiant ou rôle insuffisant | Message « Accès interdit », sans page blanche |
| `500` | Injection de faute : mot de passe DB erroné | Message neutre « Erreur serveur », sans détail technique |
