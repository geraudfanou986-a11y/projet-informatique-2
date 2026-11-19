# TP8 — Audit de sécurité

Deux points exigés par le TP8 : **vérifier que les mots de passe sont hachés dans
la base** et **décrire la protection CSRF retenue**. Les contrôles d'autorisation
et la résistance à l'injection SQL sont traités dans la matrice
(`docs/matrice_tests.md`) ; on les rappelle en fin de document.

---

## 1. Mots de passe hachés dans la base

### Ce qui est fait

- Le mot de passe n'est **jamais stocké en clair**. À l'inscription, il est haché :

  ```php
  // TP6/src/Services/AuthService.php
  $hash = password_hash($motDePasse, PASSWORD_DEFAULT); // → bcrypt, $2y$…
  $this->utilisateurs->creerCompte($email, $hash, $role, $nom, $prenom);
  ```

- À la connexion, la vérification se fait avec `password_verify()`, jamais par
  comparaison de chaînes :

  ```php
  if ($utilisateur === null || !password_verify($motDePasse, $utilisateur['hash'])) {
      return false;
  }
  ```

- Le message d'erreur est **identique** pour un compte inconnu et un mot de passe
  faux (`« Identifiants invalides »`) : pas d'énumération des comptes.
- La colonne porte un nom explicite : `utilisateur.mot_de_passe_hash` (ou `hash`
  dans la base de test), de type `VARCHAR(255)`, taille suffisante pour un hachage
  moderne.
- Le mot de passe en clair n'est jamais journalisé ni renvoyé dans une réponse.

### Vérification en base (cas n° 32)

Le test se connecte directement à MariaDB et lit la ligne créée :

```sql
SELECT hash FROM utilisateur WHERE email = 'tp8.…@univ.test';
```

Contrôles réalisés sur la valeur lue :

1. l'algorithme est bcrypt — la chaîne commence par `$2y$` ;
2. `password_verify($motDePasse, $hash)` renvoie **vrai** ;
3. `$hash !== $motDePasse` et le hachage ne **contient pas** le mot de passe.

Résultat observé (`preuves/resultats_tests.txt` ligne 42) :

```
algorithme $2y$, longueur 60, identique au mot de passe : non   → conforme
```

Un hachage bcrypt est **salé automatiquement** et **non réversible** : deux comptes
avec le même mot de passe obtiennent des hachages différents. La base ne permet donc
pas de retrouver un mot de passe, même en cas de fuite du dump.

### Limite connue / piste v2

`PASSWORD_DEFAULT` suit la recommandation de PHP (bcrypt aujourd'hui, Argon2 quand
il deviendra le défaut). Rien à changer pour l'instant ; en production on ajouterait
une politique de longueur minimale plus stricte et un contrôle contre les mots de
passe les plus courants.

---

## 2. Protection CSRF retenue

Le CSRF exploite le fait que le navigateur **joint automatiquement le cookie de
session** à une requête forgée par un site tiers. La parade retenue est un **jeton
synchronisé, porté par un en-tête personnalisé** : un site tiers ne peut pas lire
le jeton (politique de même origine) et ne peut donc pas fabriquer l'en-tête.

### Cycle du jeton

1. Le serveur crée un jeton **aléatoire de 32 octets** (`bin2hex(random_bytes(32))`,
   soit 64 caractères) et le stocke en session sous `$_SESSION['csrf']`
   (`TP6/src/Support/http.php`, `jetonCsrf()`).
2. Le client l'obtient par `GET /api/csrf` (et il est aussi renvoyé par
   `/api/connexion`, `/api/inscription` et `/api/moi`).
3. Toute **écriture authentifiée** doit présenter l'en-tête `X-CSRF-Token`.
4. Le serveur compare avec `hash_equals()` (comparaison à **temps constant**), pour
   ne pas laisser fuir le jeton par le temps de réponse.

```php
function exigerCsrf(): void
{
    $recu = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($recu) || $recu === '' || !hash_equals(jetonCsrf(), $recu)) {
        throw new HttpException(403, 'Jeton de sécurité absent ou invalide');
    }
}
```

### Où le contrôle est appliqué (`TP6/public/index.php`)

| Route | Méthode | Jeton exigé |
|---|---|---|
| `/api/connexion` | POST | non (pas encore de session) |
| `/api/inscription` | POST | non (pas encore de session) |
| `/api/csrf`, `/api/moi`, `/api/sante`, `/api/entreprises`, `/api/demandes*` (lectures) | GET | non (lecture seule) |
| `/api/deconnexion` | POST | **oui** |
| `/api/demandes` (dépôt) | POST | **oui** |
| `/api/demandes/{id}/statut` | PATCH | **oui** |
| `/api/demandes/{id}/soutenance` | PATCH | **oui** |

Seules les deux routes qui créent la session (connexion, inscription) sont
exemptées : avant connexion il n'existe pas de session à protéger.

### Côté interface (`TP7/public/js/api.js`)

La couche `fetch` ajoute l'en-tête automatiquement sur toute méthode qui n'est pas
`GET/HEAD/OPTIONS`, en récupérant le jeton au besoin. En cas de `403` « jeton »,
elle le renouvelle **une fois** puis rejoue la requête, ce qui évite les échecs
après une reconnexion.

### Défense en profondeur : cookie de session

En complément du jeton, le cookie `PHPSESSID` est émis avec `HttpOnly` et
`SameSite=Lax` (`TP6/public/index.php`) :

```
Set-Cookie: PHPSESSID=…; path=/; HttpOnly; SameSite=Lax
```

- `HttpOnly` : le cookie n'est pas lisible en JavaScript, donc un XSS ne peut pas
  voler la session ;
- `SameSite=Lax` : le cookie n'est pas envoyé sur une requête croisée (POST), ce qui
  bloque la plupart des attaques CSRF même sans jeton ;
- `Secure` est activé automatiquement dès que la requête est en HTTPS.

L'identifiant de session est **régénéré** à la connexion (`session_regenerate_id(true)`),
ce qui empêche la fixation de session.

### Preuves CSRF (cas n° 7, 10, 11, 13)

| Cas | Requête | Attendu | Observé |
|---|---|---|---|
| 7 | `GET /api/csrf` | 200 + jeton | jeton de 64 caractères |
| 10 | `PATCH …/statut` **sans** en-tête | 403 | `{"erreur":"Jeton de sécurité absent ou invalide"}` |
| 11 | `PATCH …/statut` avec jeton bidon | 403 | même refus |
| 13 | `POST /api/deconnexion` sans jeton | 403 | refus |

Une écriture légitime **avec** le bon jeton passe bien (`SOUMISE`, puis `VALIDEE`) :
le contrôle ne bloque donc pas l'usage normal.

---

## 3. Rappels : autorisations et injection SQL

- **Autorisations (ENF-03).** Le contrôle est côté **serveur**, dans le service
  (`DemandeService::detail()`), jamais dans l'interface. Un étudiant ne lit que ses
  propres demandes (`403` sinon, cas n° 21) ; un compte sans fiche étudiant est
  refusé (`403`) ; le personnel lit tout ; la liste « mes demandes » est filtrée par
  l'identifiant de la fiche (cas n° 26).
- **Injection SQL (ENF-05).** Tout le SQL passe par des **requêtes préparées** avec
  paramètres liés (`PDO::prepare` + `execute`), y compris les identifiants. La chaîne
  `1 OR 1=1` est refusée : `404` dans l'URL (elle ne correspond pas au motif
  `(\d+)`), `400` dans le corps JSON (`entrepriseId` non entier), **sans aucune
  ligne créée** (cas n° 27 et 28).
- **Fuites d'information.** Les erreurs techniques (`PDOException`, etc.) sont
  écrites dans le journal du serveur et **jamais** renvoyées : la réponse générique
  est `500 {"erreur":"Erreur serveur"}`.
