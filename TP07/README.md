# TP07 — Interface et intégration

Interface Web accessible reliée à l'API du TP06. Données fictives uniquement.

## Contenu

| Fichier | Rôle |
|---|---|
| `public/index.html` | Accueil, connexion **et inscription de tout visiteur**, comptes de démonstration en un clic |
| `public/app.html` | Espace connecté unifié : tableau de bord, demandes, soutenances, historique, profil |
| `public/connexion.html`, `public/etudiant.html`, `public/responsable.html` | Anciennes pages conservées : elles redirigent vers l'interface unifiée |
| `public/parcours.html`, `public/scenario.html` | Vraies pages HTML reprenant la charte finale (plus de liens vers des `.md` bruts) |
| `public/assets/js/api.js` | Appels `fetch` + gestion lisible des erreurs 400/401/500, CSRF |
| `public/assets/js/app.js` | Rendu sécurisé avec `textContent`, notifications, relecture automatique (15 s) |
| `public/assets/js/auth.js` | Connexion, inscription et comptes de démonstration |
| `public/assets/css/style.css` | Charte graphique commune à toutes les pages |
| `public/router.php` | Délègue `/api/*` à l'API du TP06 sur la même origine |
| `docs/parcours.md`, `docs/parcours.svg` | Parcours des deux rôles dessinés avant le codage |
| `docs/scenario_bout_en_bout.md` | Scénario complet créer → retrouver → valider → rafraîchir |
| `preuves/` | Résultats des tests réels (balises HTML, erreurs, persistance) |

## Prérequis

- PHP 8, MariaDB ou MySQL, navigateur.
- Le dossier `TP06` présent à côté de `TP07` (le routeur y délègue les appels `/api/*`).

## Installation et lancement

```bash
# 1. Base de données (si elle n'existe pas déjà)
mysql -u root -p -e "CREATE DATABASE stages CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p stages < TP03/001_schema.sql
mysql -u root -p stages < TP03/002_seed.sql
mysql -u root -p stages < TP06/sql/01_complements.sql

# 2. Serveur unique pour l'interface et l'API
cd TP07
php -S 127.0.0.1:8001 -t public public/router.php
```

Ouvrir ensuite <http://127.0.0.1:8001/>.

N'importe qui peut créer son compte depuis l'accueil → onglet « Inscription ».
Le mot de passe est haché avec `password_hash()` et une fiche `etudiant` est créée
dans la même transaction pour un compte étudiant. Le compte est connecté automatiquement.

Comptes fictifs de démonstration (facultatifs) :

| Rôle | Email | Mot de passe |
|---|---|---|
| Étudiant | `koffi.adjovi@univ.test` | `Etudiant123!` |
| Responsable | `sylvie.hounkpe@univ.test` | `Responsable123!` |

Si MariaDB n'écoute pas sur le port 3306, exporter les variables avant de lancer PHP :
`DB_PORT=3307` (et `DB_PASS=…` si besoin).

## Depuis VS Code

PHP est interprété : il n'y a pas de compilation. Ouvre le dossier
`C:\Users\ELITEBOOK 840 G5\Desktop\projet informatique 2` dans VS Code, puis :

1. Démarre MariaDB (XAMPP Control Panel, ou le serveur sur le port 3307).
2. `Ctrl+Shift+B` → cette tâche lance le serveur et ouvre <http://127.0.0.1:8001/>.
3. Si la base n'existe pas : Terminal → Run Task… → **TP07 : préparer la base de test**.

La configuration est dans `.vscode/tasks.json`. Les variables `DB_PORT` / `DB_PASS` sont
ajustables dans ce fichier si ton MariaDB écoute sur le port 3306 ou demande un mot de passe.

L'extension **Live Server** ne suffit pas : elle sert des fichiers statiques et ne sait pas
exécuter `router.php`. Il faut le serveur PHP intégré (tâche ci-dessus).

Pour voir les changements : modifie un fichier, puis rafraîchis le navigateur (`Ctrl+F5`).

## Points de contrôle du TP07

1. **Formulaires accessibles** : chaque champ a un `<label for>` lié à son `id`, un type adapté (`email`, `number`, `password`) et une zone `<p role="alert">` voisine du champ. Les contrôles navigateur (`required`) ne remplacent pas ceux du serveur.
2. **Erreurs lisibles** : `400` affiché près du champ, `401` redirection vers la connexion avec message, `500` message neutre. Aucune page blanche.
3. **Rendu sécurisé** : toute donnée affichée passe par `textContent`. Le test est fait avec un vrai motif de refus contenant `<script>alert(1)</script> <b>test</b>` : il s'affiche tel quel chez l'étudiant, sans balise créée ni boîte de dialogue. Voir `preuves/test_balises.txt` et `preuves/capture_etudiant_xss.png`.
4. **Intégration** : le scénario se termine par un rafraîchissement de page et une relecture depuis la base, décrit dans `docs/scenario_bout_en_bout.md` et `preuves/scenario_bout_en_bout.txt`. Voir aussi `preuves/capture_responsable_persistance.png`.
5. **Temps réel** : les listes sont relues toutes les 15 s lorsqu'aucune modale n'est ouverte et qu'aucun champ n'a le focus ; une décision fait clignoter la ligne modifiée et affiche une notification. Voir `preuves/inscription_et_temps_reel.txt`.

Pour observer les deux rôles en direct, utilisez deux navigateurs ou une fenêtre privée :
les onglets d'un même navigateur partagent le cookie `PHPSESSID`.
