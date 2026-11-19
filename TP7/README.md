# TP7 — Interface et intégration

Interface Web accessible reliée à l'API du TP6. Données fictives uniquement.

## Contenu

| Fichier | Rôle |
|---|---|
| `public/index.html` | Accueil, liens vers les parcours et le scénario |
| `public/connexion.html` | Écran de connexion des deux rôles |
| `public/etudiant.html` | Liste de ses demandes, formulaire de dépôt, détail, test XSS |
| `public/responsable.html` | Liste des demandes, décision, rafraîchissement |
| `public/js/api.js` | Appels `fetch` + gestion lisible des erreurs 400/401/500 |
| `public/js/commun.js` | Rendu sécurisé avec `textContent`, messages, déconnexion |
| `public/router.php` | Délègue `/api/*` à l'API du TP6 sur la même origine |
| `docs/parcours.md`, `docs/parcours.svg` | Parcours des deux rôles dessinés avant le codage |
| `docs/scenario_bout_en_bout.md` | Scénario complet créer → retrouver → valider → rafraîchir |
| `preuves/` | Résultats des tests réels (balises HTML, erreurs, persistance) |

## Prérequis

- PHP 8, MariaDB ou MySQL, navigateur.
- Le dossier `TP6` présent à côté de `TP7` (le routeur y délègue les appels `/api/*`).

## Installation et lancement

```bash
# 1. Base de données (si elle n'existe pas déjà)
mysql -u root -p -e "CREATE DATABASE stages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p stages < TP3/001_schema.sql
mysql -u root -p stages < TP3/002_seed.sql
mysql -u root -p stages < TP6/sql/01_complements.sql

# 2. Serveur unique pour l'interface et l'API
cd TP7
php -S 127.0.0.1:8001 -t public public/router.php
```

Ouvrir ensuite <http://127.0.0.1:8001/>.

Comptes fictifs :

| Rôle | Email | Mot de passe |
|---|---|---|
| Étudiant | `koffi.adjovi@univ.test` | `Etudiant123!` |
| Responsable | `sylvie.hounkpe@univ.test` | `Responsable123!` |

Si MariaDB n'écoute pas sur le port 3306, exporter les variables avant de lancer PHP :
`DB_PORT=3307` (et `DB_PASS=…` si besoin).

## Points de contrôle du TP7

1. **Formulaires accessibles** : chaque champ a un `<label for>` lié à son `id`, un type adapté (`email`, `number`, `password`) et une zone `<p role="alert">` voisine du champ. Les contrôles navigateur (`required`) ne remplacent pas ceux du serveur.
2. **Erreurs lisibles** : `400` affiché près du champ, `401` redirection vers la connexion avec message, `500` message neutre. Aucune page blanche.
3. **Rendu sécurisé** : toute donnée affichée passe par `textContent` ; le test avec `<b>test</b>` et `<script>alert(1)</script>` est détaillé dans `preuves/test_balises.txt`.
4. **Intégration** : le scénario se termine par un rafraîchissement de page et une relecture depuis la base, décrit dans `docs/scenario_bout_en_bout.md` et `preuves/scenario_bout_en_bout.txt`.
