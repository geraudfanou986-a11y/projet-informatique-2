# Questions individuelles — un membre, quatre réponses

Le contrôle oral est individuel (section 10.4) : chacun doit expliquer **sa propre**
contribution en s'appuyant sur l'historique Git (`TP10/preuves/git_log.txt`). Les
réponses ci-dessous sont des aides-mémoire, à dire avec ses propres mots.

---

## Ange — modélisation et données

**Règle métier.** Une demande appartient à un étudiant, référence une entreprise et
éventuellement un encadreur ; ces liens sont des clés étrangères, donc une demande ne
peut pas pointer vers une fiche inexistante. C'est la règle d'intégrité du modèle,
visible dans `TP3/001_schema.sql`.

**Requête.** `SELECT COUNT(*) FROM demande_stage;` utilisée pour contrôler le jeu
d'essai après import. Réponse attendue : **4** demandes (ainsi que 3 étudiants, 2
entreprises, 2 encadreurs, 1 soutenance) — c'est le contrôle du TP3.

**Test.** `TP3/003_verification.sql` : il rejoue les `COUNT(*)` du jeu d'essai et
sert de preuve que le schéma importé correspond au diagramme. En cas d'écart,
l'import est refait depuis `001_schema.sql` puis `002_seed.sql`.

**Commit.** `9cf601f` — « Ajoute le TP3 : modèle UML et schéma SQL ». Je montre ce
commit, puis `git show 9cf601f --stat` pour nommer les diagrammes et les scripts SQL
ajoutés.

---

## Vanessa — architecture et contrat d'API

**Règle métier.** Le navigateur ne parle jamais directement à la base : toutes les
écritures passent par l'API JSON en `/api/*`, ce qui permet de placer la validation et
le contrôle d'accès en un seul endroit. Le choix est tracé dans l'ADR-001.

**Requête.** `SELECT email, hash, role FROM utilisateur WHERE email = :email` avec
une requête préparée PDO (valeur transmise séparément du texte). Elle sert à
l'authentification ; le mot de passe n'est jamais comparé en clair.

**Test.** Cas 1 de la matrice TP8 : `GET /api/sante` → `HTTP 200
{"statut":"ok","base":"joignable"}`. C'est la preuve que le squelette, la
connexion PDO et le contrat d'API tiennent ensemble.

**Commit.** `2463f76` — « Ajoute le TP4 : squelette technique, contrat d'API et
ADR-001 ». Je montre le contrat d'API et l'ADR, puis j'explique que le squelette a
été remplacé par l'API réelle en TP6 sans changer les URL.

---

## Géraud — backend, tests et sécurité

**Règle métier.** Les transitions sont vérifiées côté serveur : une demande ne passe
qu'en `BROUILLON → SOUMISE → VALIDEE`, un refus sans motif donne `400`, une validation
répétée donne `409`, et `VALIDEE → REFUSEE` est interdite. Le service, pas le
navigateur, applique la règle.

**Requête.** `SELECT hash FROM utilisateur WHERE email = :email` puis
`password_verify($motDePasse, $hash)`. Vérification faite en TP8 : le hash stocké
commence par `$2y$` et `password_verify()` renvoie vrai — aucun mot de passe en clair.

**Test.** Cas 17 (T-03, validation répétée → `409`, statut inchangé) et cas 21
(étudiant B sur le dossier de A → `403`, sans donnée). Le cas 21 avait échoué lors
de la première exécution à cause de `utilisateur.id` utilisé à la place de
`etudiant.id` ; le correctif est dans `DemandeController.php`.

**Commit.** `f2b6366` — « Ajoute le TP8 (plan de tests et audit de securite) ». Je
montre `git show f2b6366 --stat`, la matrice 32/32 et le fichier de preuves brut ;
je cite aussi `543ac04` qui a retiré le numéro du groupe du README.

---

## Si la question porte sur le déploiement

Réponse commune : le compte `stages_deploiement` n'a que six privilèges sur une seule
base (`TP9/sql/00_utilisateur.sql`) ; `/api/sante` répond `200` sur le second poste
(`TP9/preuves/sante_http.txt`) ; la sauvegarde a été restaurée avec des `COUNT(*)`
identiques (`TP9/preuves/avant.json` et `apres.json`). L'incident `LOCK TABLES` est
décrit dans `TP9/docs/incident.md`.
