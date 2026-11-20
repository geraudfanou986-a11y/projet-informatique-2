# Contrat d'API - TP06 (implémenté)

Base : `http://localhost:8000` · Format : JSON (`Content-Type: application/json; charset=utf-8`).
Les routes métier exigent une session (cookie PHPSESSID) sauf `/api/sante` et `/api/connexion`.

| Méthode | Chemin | Rôle | Corps | Succès | Erreurs |
|---|---|---|---|---|---|
| GET | `/api/sante` | Public | – | `200` | `500` |
| POST | `/api/connexion` | Public | `{"email":"...","motDePasse":"..."}` | `200` | `400`, `401` |
| POST | `/api/deconnexion` | Authentifié | – | `200` | – |
| GET | `/api/demandes` | responsable, secretariat | – | `200` liste | `401`, `403` |
| GET | `/api/demandes/mes` | etudiant | – | `200` liste filtrée | `401`, `403` |
| POST | `/api/demandes` | etudiant | `{"entrepriseId":3}` | `201` | `400`, `401`, `403` |
| PATCH | `/api/demandes/{id}/statut` | responsable | `{"statut":"VALIDEE"}` ou `{"statut":"REFUSEE","motif":"..."}` | `200` | `400`, `401`, `403`, `404`, `409` |

## Table des transitions

| État courant | Autorisé | Refusé |
|---|---|---|
| `BROUILLON` | `SOUMISE` | `VALIDEE`, `REFUSEE` |
| `SOUMISE` | `VALIDEE`, `REFUSEE` | `BROUILLON` |
| `REFUSEE` | `BROUILLON` | `VALIDEE` |
| `VALIDEE` | – (état final) | `REFUSEE` → **409** |

Un refus sans `motif` donne `400`. Une transition interdite donne `409`, sans aucune écriture en base.

## Sécurité

- Mot de passe haché avec `password_hash()` et vérifié avec `password_verify()` ; aucun mot de passe en clair en base.
- `session_regenerate_id(true)` après connexion : empêche la fixation de session (un attaquant ne peut pas imposer un identifiant de session connu).
- Requêtes préparées partout dans `src/Repositories`.
- Le détail des exceptions PDO reste dans `error_log` ; le client reçoit un message neutre.
