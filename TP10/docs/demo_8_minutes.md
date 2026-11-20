# Scénario de démonstration — 8 minutes

Ordre imposé par le cours (section 10.2) : contexte → dépôt → validation et
programmation → contrôle d'accès → tests et bilan. Les données sont fictives
(`@univ.test`) et préparées à l'avance. Le serveur utilisé est celui du poste
principal : `http://127.0.0.1:8001` (interface TP07 + API TP06).

Comptes de démonstration :

| Rôle | Email | Mot de passe |
|---|---|---|
| Étudiant A (déposant) | `koffi.adjovi@univ.test` | `Etudiant123!` |
| Étudiant B (curieux) | `aicha.bello@univ.test` | mot de passe fictif du compte de test |
| Responsable | `sylvie.hounkpe@univ.test` | `Responsable123!` |

> Deux navigateurs (ou une fenêtre privée) sont nécessaires : les onglets d'un même
> navigateur partagent `PHPSESSID` et écraseraient la session du rôle précédent.

## Minute par minute

| Minutes | Séquence | Action à l'écran | Preuve montrée |
|---|---|---|---|
| **0-1** | Contexte et problème | Ouvrir le README racine et la fiche `TD1/`. Énoncer le problème : un étudiant dépose une demande, un responsable la valide, l'accès doit être cloisonné. | Indicateurs du cadrage : 3 rôles, 5 exigences fonctionnelles, portée v1/v2 lisible dans `TD2/`. |
| **1-3** | Dépôt d'une demande | Se connecter en tant que A. Récupérer `GET /api/csrf`, puis `POST /api/demandes {"entrepriseId":1}` avec l'en-tête `X-CSRF-Token`. | **Ligne créée en base** : `HTTP 201 {"statut":"BROUILLON"}`, puis `SELECT COUNT(*) FROM demande_stage` avant/après dans `phpMyAdmin` (4 → 5). |
| **3-5** | Validation et programmation | A soumet (`PATCH .../statut {"statut":"SOUMISE"}`). Le responsable valide (`VALIDEE`) puis programme `PATCH .../soutenance {"date_heure":"2026-12-11 10:00:00","salle":"B12"}`. | **Statut et soutenance** : `HTTP 200 {"statut":"VALIDEE"}` puis `{"date_heure":"2026-12-11 10:00:00","salle":"B12"}` ; ligne visible dans `soutenance`. |
| **5-6** | Contrôle d'accès | Dans le second navigateur, se connecter en tant que B et appeler `GET /api/demandes/{id de A}`. Enchaîner avec `GET /api/demandes/1 OR 1=1`. | **Réponse 403** sans aucune donnée (`{"erreur":"Acces interdit"}`), puis **404** sur l'injection (`{"erreur":"Route inconnue"}`). |
| **6-8** | Tests et bilan | Ouvrir `TP08/docs/matrice_tests.md` et `TP08/preuves/resultats_tests.txt`. Montrer un hash bcrypt en base. | **Matrice de tests** : 32/32 conformes, quatre cas limites T-01 à T-04, hash `$2y$…` et jeton CSRF de 64 caractères. |

## Phrases d'introduction (à dire, pas à lire)

- **0-1** : « Le projet couvre cinq besoins : inscrire un étudiant, déposer une demande,
  la valider ou la refuser motivée, programmer une soutenance et empêcher un étudiant
  de lire le dossier d'un autre. »
- **3-5** : « La transition est vérifiée dans le service, pas dans le navigateur : une
  demande validée ne peut plus être refusée, et une soutenance n'est possible qu'après
  validation. »
- **5-6** : « Le contrôle est fait côté serveur à chaque requête. Même avec une session
  valide, B obtient 403 et aucun champ de la demande de A n'est renvoyé. »
- **6-8** : « Notre matrice compte 32 cas exécutés. Les deux premiers échecs observés
  étaient un vrai bug d'identifiant, corrigé puis rejoué : 32/32. »

## Erreurs à éviter pendant la démo

- Improviser un compte ou une demande : tout est préparé avant de lancer le chronomètre.
- Montrer le code avant la preuve : la preuve visuelle vient d'abord, l'explication ensuite.
- Oublier le jeton CSRF : un `PATCH` sans en-tête donne 403 et casserait le scénario.
- Dépasser 8 minutes sur la validation : la programmation de soutenance est la partie
  la plus lente, elle est chronométrée à part dans `docs/repetition.md`.
