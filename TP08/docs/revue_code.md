# TP08 — Fiche de revue de code

## Objet de la revue

| | |
|---|---|
| **Dépôt relu** | <https://github.com/thelabofferdi/pjinfo2> |
| **Branche** | `main` (la plus complète ; la branche `tp7` porte une version plus ancienne de l'interface) |
| **Périmètre** | squelette PHP `TP07/squelette/` (interface + API), schéma `TP03/001_schema.sql`, scénario `TP07/tests/e2e.mjs` |
| **Méthode** | lecture du code source, des contrôleurs, services, dépôts et du routeur ; recoupement avec leur schéma SQL et leurs tests |
| **Réciproque** | notre dépôt `geraudfanou986-a11y/projet-informatique-2` est relu par l'autre groupe selon la même consigne |

Chaque remarque est **classée** : *Bloquant* (à corriger avant toute remise),
*Majeur* (défaut réel, à corriger rapidement), *Mineur* (amélioration utile),
*Suggestion* (confort). Chaque point cite le fichier, l'extrait et la correction
proposée.

---

## Remarque 1 — Cookie de session sans `HttpOnly` ni `SameSite` — **Majeur (sécurité)**

**Fichier :** `TP07/squelette/src/Http/Session.php`

```php
public static function demarrer(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();          // aucun paramètre de cookie
    }
}
```

**Constat.** `session_start()` est appelé sans paramètres. Par défaut, PHP n'active
`HttpOnly` et `SameSite` que si `php.ini` le demande : le cookie `PHPSESSID` est
donc, en pratique, **lisible en JavaScript** et **envoyé sur requêtes croisées**.
Ils protègent correctement les écritures par un jeton CSRF, mais ce jeton ne couvre
pas tout : la moindre faille XSS devient un vol de session, et le cookie reste un
vecteur CSRF pour les rares routes sans jeton.

**Impact.** Un XSS (même mineur) suffit à exfiltrer la session ; la protection CSRF
est la seule barrière restante.

**Correction proposée** — avant `session_start()` :

```php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',   // 'Strict' si aucun lien externe n'entre dans l'app
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();
```

> Côté projet, c'est exactement la correction que nous avons appliquée après avoir
> constaté le même manque : `Set-Cookie: PHPSESSID=…; HttpOnly; SameSite=Lax`
> (`TP06/public/index.php`). Le contrôle est vérifiable en une commande :
> `curl -i http://…/api/csrf | grep Set-Cookie`.

---

## Remarque 2 — Limiteur de connexion fragile (fichier temporaire, sans verrou, clé contournable) — **Majeur (sécurité / robustesse)**

**Fichier :** `TP07/squelette/src/Http/LimiteurConnexion.php`

```php
private static function fichier(string $cle): string
{
    return sys_get_temp_dir() . '/limite_connexion_' . hash('sha256', $cle);
}

public static function echec(string $cle): void
{
    $donnees = self::lire($cle) ?? ['debut' => time(), 'echecs' => 0];
    $donnees['echecs']++;
    file_put_contents(self::fichier($cle), json_encode($donnees)); // ni LOCK_EX, ni flock
}
```

**Constats.**
1. **Condition de course.** Le cycle lire → incrémenter → écrire n'est pas atomique.
   Deux tentatives simultanées peuvent lire la même valeur et n'incrémenter qu'une
   fois : le compteur sous-estime les échecs et le seuil de 5 peut être dépassé.
2. **Clé contournable.** La clé est `adresse|email` (`AuthService::connecter()`). Un
   attaquant qui change d'adresse IP (proxies, réseau mobile) n'atteint jamais 5
   échecs sur un même couple : le limiteur ne protège pas vraiment le **compte**
   visé. À l'inverse, une adresse partagée (salle de TP, NAT) peut bloquer des
   utilisateurs légitimes.
3. **Stockage partagé.** `sys_get_temp_dir()` est lisible par les autres processus
   de la machine ; le nom du fichier révèle qu'un compte est ciblé.

**Impact.** La protection contre le bourrage d'identifiants (credential stuffing) est
largement inopérante.

**Correction proposée.** Compter **par compte** (et non par couple) *et* par IP, avec
un stockage atomique : `flock()` sur un fichier dédié, ou une table
`tentative_connexion(cle, echecs, fenetre)` en base, ou un cache (APCu/Redis).
Exemple minimal :

```php
$f = fopen(self::fichier($cle), 'c+');
flock($f, LOCK_EX);
// lire, incrémenter, réécrire
flock($f, LOCK_UN);
fclose($f);
```

---

## Remarque 3 — Jokers `LIKE` non échappés dans la recherche par nom — **Mineur (robustesse)**

**Fichier :** `TP07/squelette/src/Repositories/DemandeRepository.php`

```php
if ($nom !== null) {
    $conditions[] = "CONCAT(ue.nom, ' ', ue.prenom) LIKE :nom";
    $parametres[':nom'] = '%' . $nom . '%';
}
```

**Constat.** La valeur est bien **liée** (aucune injection SQL : c'est un bon point).
En revanche, les caractères `%` et `_` saisis par l'utilisateur sont interprétés
comme des **jokers** : une recherche contenant `%` renvoie toutes les demandes, et
`_` remplace n'importe quel caractère. Le comportement surprend l'utilisateur et
peut être exploité pour balayer des données.

**Impact.** Faible (fuite déjà bornée par les droits), mais la recherche ne fait pas
ce qu'elle annonce.

**Correction proposée.** Échapper les jokers avant de les encadrer :

```php
$nom = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $nom);
$parametres[':nom'] = '%' . $nom . '%';
```

(ou `LIKE :nom ESCAPE '\\'` selon le dialecte).

---

## Remarque 4 — L'interface demande le jeton CSRF même sur la connexion ; code mort — **Mineur (qualité)**

**Fichier :** `TP07/squelette/public/app.js`

```js
async function obtenirJeton() {
  if (!jetonCsrf) {
    const reponse = await fetch('api/csrf');   // 401 avant connexion
    if (reponse.ok) jetonCsrf = (await reponse.json()).jeton;
  }
  return jetonCsrf;
}

async function appeler(methode, chemin, corps) {
  const options = { method: methode, headers: {} };
  if (methode !== 'GET') {
    const jeton = await obtenirJeton();        // appelé aussi pour POST /api/connexion
    if (jeton) options.headers['X-CSRF-Token'] = jeton;
  }
  ...
}
```

**Constats.**
1. `obtenirJeton()` est déclenché pour **toute** méthode différente de `GET`, y
   compris `POST /api/connexion`, qui est pourtant exempté de jeton côté serveur
   (`Routeur::SANS_JETON`). Comme `GET /api/csrf` exige une session
   (`Session::jetonCsrf()` appelle `utilisateur()`), **chaque connexion déclenche
   d'abord un `401` inutile** : bruit dans les journaux et requête perdue.
2. `texteDErreur()` définit `MESSAGE_DROITS` pour un `403` sans message, mais le
   serveur renvoie toujours `{"erreur":…}` : cette branche n'est jamais atteinte
   (code mort).

**Impact.** Faible, mais révélateur : la couche réseau ne connaît pas la liste des
routes exemptées, contrairement au routeur serveur.

**Correction proposée.** Aligner le client sur le serveur : ne pas demander de jeton
pour `/api/connexion` et `/api/inscription`, et rafraîchir le jeton **après** une
connexion réussie (il est régénéré par `Session::ouvrir`). Supprimer ou justifier
`MESSAGE_DROITS`.

---

## Autres observations (hors classement)

- **Bon point — contrôle d'accès complet.** `DemandeService::detail()` et `changer()`
  vérifient le rôle *et* l'appartenance (`etudiant_id`/`fiche_id`) côté serveur ;
  c'est exactement la bonne approche, et cela couvre le cas « B lit le dossier de A »
  du TP08.
- **Bon point — CSRF appliqué au bon endroit.** Le jeton est exigé de façon
  centralisée dans `Routeur::repondre()` (toute méthode ≠ GET sauf `/api/connexion`),
  ce qui évite d'oublier une route. Comparaison via `hash_equals()` : correct.
- **Bon point — SQL.** Toutes les requêtes sont préparées, y compris `rechercher()` ;
  le verrou `FOR UPDATE` sur les transitions évite les décisions concurrentes.
- **Suggestion — reproductibilité des tests.** `TP07/tests/e2e.mjs` suppose `docker`,
  `google-chrome`, la commande `sleep` et le port `8081` codé en dur. Sur une autre
  machine (Windows, sans Docker), le scénario ne démarre pas. Prévoir des variables
  d'environnement et un message d'erreur explicite si Chrome est absent.
- **Suggestion — `Session::detruire()`** ne supprime pas le cookie `PHPSESSID` côté
  navigateur ; l'ajouter (`setcookie(..., time()-3600)`) termine proprement la
  déconnexion.

---

## Synthèse

| # | Remarque | Fichier | Classe |
|---|---|---|---|
| 1 | Cookie de session sans `HttpOnly`/`SameSite` | `Http/Session.php` | **Majeur (sécurité)** |
| 2 | Limiteur de connexion fragile | `Http/LimiteurConnexion.php` | **Majeur (sécurité/robustesse)** |
| 3 | Jokers `LIKE` non échappés | `Repositories/DemandeRepository.php` | **Mineur (robustesse)** |
| 4 | Jeton demandé sur la connexion ; code mort | `public/app.js` | **Mineur (qualité)** |

Aucune remarque **bloquante** : leur code est structuré en couches, leurs requêtes
sont préparées, leur contrôle d'accès et leur CSRF sont présents. Les deux remarques
majeures portent sur la **défense en profondeur** (cookie, limiteur) : ce sont des
durcissements, pas des failles exploitées en l'état.
