# TP7 — Scénario de bout en bout documenté

Objectif : prouver que l'interface est réellement reliée à l'API et à la base, et que le statut
reste après rafraîchissement de la page. Résultats bruts dans [`../preuves/scenario_bout_en_bout.txt`](../preuves/scenario_bout_en_bout.txt).

| Étape | Rôle | Action dans l'interface | Appel | Résultat attendu |
|---|---|---|---|---|
| 1 | Étudiant | Se connecter avec le compte fictif | `POST /api/connexion` | `200`, redirection vers `etudiant.html` |
| 2 | Étudiant | Consulter « Mes demandes » | `GET /api/demandes/mes` | `200`, tableau rempli |
| 3 | Étudiant | Saisir `2` et cliquer sur « Envoyer la demande » | `POST /api/demandes` | `201`, message « Demande n° 5 créée… », liste rechargée |
| 4 | Responsable | Se connecter | `POST /api/connexion` | `200`, redirection vers `responsable.html` |
| 5 | Responsable | Choisir « Refuser » sans motif | `PATCH /api/demandes/5/statut` | `400`, message près du champ motif, aucune écriture |
| 6 | Responsable | Choisir « Soumettre », puis « Valider » | `PATCH /api/demandes/5/statut` | `200` deux fois, messages de succès |
| 7 | Responsable | **Rafraîchir la page** (F5) | `GET /api/demandes` | La demande n° 5 affiche toujours `VALIDEE` |

## Astuce pour observer les deux rôles

Les onglets d'un même navigateur partagent le cookie `PHPSESSID` : se connecter comme responsable
remplace la session étudiant. Pour suivre les deux rôles en direct, utilisez deux navigateurs
différents ou une fenêtre privée. Sinon, déconnectez-vous simplement entre les deux rôles.
L'actualisation automatique (15 s) relit la base à chaque cycle.

## Résultat observé

```
Demande n° 5 créée avec le statut BROUILLON.
Demande n° 5 : statut mis à jour en SOUMISE.
Demande n° 5 : statut mis à jour en VALIDEE.
Après F5 : 5  1  2  VALIDEE  —
En base  : {"id":5,"etudiant_id":1,"entreprise_id":2,"statut":"VALIDEE","motif_refus":null}
Historique : BROUILLON -> SOUMISE -> VALIDEE
```

## Pourquoi cette dernière étape compte

Sans le rafraîchissement, le statut pourrait venir uniquement de la mémoire du navigateur.
Après F5, la page refait un appel `GET /api/demandes` : la valeur affichée provient donc de la base.
C'est cette étape qui prouve la persistance.

## Cas d'erreurs joués pendant le scénario

| Code | Déclenchement | Comportement de l'interface |
|---|---|---|
| `400` | Refus sans motif | Message « Motif obligatoire pour un refus » sous le champ motif |
| `401` | Session absente | Redirection vers `connexion.html?raison=session` avec message |
| `403` | Session étudiant sur une page responsable | Message « Acces interdit », sans page blanche |
| `500` | Injection de faute : mot de passe DB erroné | Message neutre « Erreur serveur », sans détail technique |
