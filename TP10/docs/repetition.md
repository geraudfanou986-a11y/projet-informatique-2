# Répétition chronométrée et solution de repli

Une démonstration se répète. Le scénario de `docs/demo_8_minutes.md` est découpé en
cinq blocs ; chaque bloc a une durée cible et une marge. Le total cible est **7 min 45 s**
pour laisser 15 s de marge avant la limite de 8 minutes.

## Grille de répétition

| Bloc | Durée cible | Marge | À vérifier à la fin du bloc | Temps mesuré |
|---|---:|---:|---|---|
| 0-1 Contexte | 1 min 00 | ±10 s | Les 5 besoins sont énoncés, sans lire le README | ☐ ___ |
| 1-3 Dépôt | 2 min 00 | ±15 s | `201` + `COUNT(*)` avant/après visibles | ☐ ___ |
| 3-5 Validation + soutenance | 2 min 00 | ±20 s | `VALIDEE` et la ligne `soutenance` visibles | ☐ ___ |
| 5-6 Accès | 1 min 00 | ±10 s | `403` puis `404` affichés | ☐ ___ |
| 6-8 Tests et bilan | 1 min 45 | ±10 s | 32/32 et le hash `$2y$` montrés | ☐ ___ |
| **Total** | **7 min 45** | **15 s** | | ☐ ___ |

Règle de décision : si un bloc dépasse sa marge deux fois de suite, on supprime une
action secondaire (l'injection `1 OR 1=1` en minute 5-6, ou l'ouverture de phpMyAdmin
en minute 1-3 si le `201` suffit). Les cinq séquences imposées restent toutes montrées.

## Protocole de la dernière répétition

1. Ouvrir **avant** de lancer le chronomètre : les deux navigateurs connectés, le
   terminal avec `php -S 127.0.0.1:8001 -t TP07/public TP07/public/router.php`, la base
   de test `tp7_corrige_test`, `TP08/preuves/resultats_tests.txt` et la solution de repli.
2. Lancer le chronomètre au premier mot de la phrase d'introduction.
3. Arrêter au dernier mot de la conclusion, pas à la fermeture des onglets.
4. Noter les temps dans la grille ci-dessus ; refaire une seule répétition complète
   si le total dépasse 8 minutes.
5. Rôle du porte-parole : parler ; rôle du second membre : préparer la preuve suivante
   à l'écran sans parler ; rôle du troisième : surveiller le chronomètre et annoncer
   « 30 s restantes ».

## Solution de repli

Si le réseau, le serveur PHP ou MariaDB tombe pendant la soutenance, la démonstration
continue **sans requête en direct**, dans cet ordre :

| Preuve de repli | Fichier à ouvrir |
|---|---|
| Page de santé `200` | `TP04/` (compte rendu du test de la route) |
| Demande créée et statut `VALIDEE`, soutenance programmée | `TP08/preuves/resultats_tests.txt` (cas 9, 16, 19) |
| Refus `403` et `404` sur l'injection | `TP08/preuves/resultats_tests.txt` (cas 21, 27) et `TP08/docs/cas_limites.md` |
| Matrice complète | `TP08/preuves/resultats_tests.json`, à filtrer avec `'cas': 14` à `21` |
| Installation et sauvegarde | `TP09/preuves/sante_http.txt`, `TP09/preuves/avant.json`, `TP09/preuves/apres.json` |

Ces fichiers sont versionnés : ils restent consultables même si le poste de démonstration
est remplacé par un autre. Une capture d'écran de la séquence 1-3 peut être ajoutée dans
`TP10/preuves/` par le groupe ; elle n'est pas indispensable puisque les sorties texte
suffisent à prouver chaque étape.

## Ce qu'il faut avoir ouvert au moment de commencer

- Deux navigateurs avec les sessions A et B déjà authentifiées.
- Le terminal du serveur (pour redémarrer en 5 secondes si besoin).
- phpMyAdmin ou un `SELECT COUNT(*)` prêt à être exécuté.
- La solution de repli dans un onglet séparé, **jamais** dans l'onglet de démonstration.
