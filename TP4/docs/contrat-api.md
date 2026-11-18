# Contrat d'API - TP4

Base : `http://localhost:8000` · Format : JSON (`Content-Type: application/json; charset=utf-8`).

## Route de santé (implémentée en TP4)

| Méthode | Chemin | Rôle autorisé | Succès | Erreurs |
|---|---|---|---|---|
| GET | `/api/sante` | Public | `200` si la base est joignable | `500` si la base est injoignable |

Réponse `200` :

```json
{"statut":"ok","base":"joignable"}
```

Réponse `500` :

```json
{"erreur":"Base de donnees indisponible"}
```

## Les trois routes métier (contrat à respecter en TP6)

| Méthode | Chemin | Rôle autorisé | Corps attendu | Succès | Erreurs |
|---|---|---|---|---|---|
| GET | `/api/demandes` | Responsable, secrétariat | Aucun | `200` : liste des demandes | `401` session absente, `403` rôle insuffisant |
| POST | `/api/demandes` | Étudiant | `{"entrepriseId": 3}` | `201` : demande créée (`BROUILLON`) | `400` données invalides, `401` session absente |
| PATCH | `/api/demandes/{id}/statut` | Responsable | `{"statut":"VALIDEE"}` ou `{"statut":"REFUSEE","motif":"..."}` | `200` : statut mis à jour | `400` données invalides, `403` rôle insuffisant, `404` demande absente, `409` transition interdite |

### Exemple : PATCH /api/demandes/7/statut

Requête :

```http
PATCH /api/demandes/7/statut HTTP/1.1
Content-Type: application/json

{"statut":"REFUSEE","motif":"Convention incomplete"}
```

Réponses :

| Code | Cas | Corps |
|---|---|---|
| `200` | Transition autorisée | `{"id":7,"statut":"REFUSEE"}` |
| `400` | Motif de refus absent | `{"erreur":"Motif obligatoire pour un refus"}` |
| `401` | Session expirée | `{"erreur":"Non authentifie"}` |
| `403` | Étudiant qui tente de valider | `{"erreur":"Acces interdit"}` |
| `404` | Demande 999 inexistante | `{"erreur":"Demande introuvable"}` |
| `409` | Validation répétée | `{"erreur":"Transition interdite"}` |
| `500` | Erreur serveur | `{"erreur":"Erreur serveur"}` |

Le détail technique (message PDO, trace) reste dans les journaux du serveur, jamais dans la réponse.
