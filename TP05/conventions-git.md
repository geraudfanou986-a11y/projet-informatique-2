# Conventions Git de l'équipe

## 1. Dépôt et premier commit

Un seul dépôt pour le groupe. Le first commit contient le `.gitignore` et le `README` :

```bash
git init -b main
git add .gitignore README.md
git commit -m "Initialise le depot et le README"
git status
```

Le `.gitignore` exclut `.env`, `vendor/`, les logs et les sauvegardes de base.

## 2. Messages de commit

 courts, à l'impératif, et disent ce qui change.

- Bien : `Ajoute la route POST /api/demandes`, `Corrige le code 409 sur validation répétée`.
- Mal : `modifs`, `fix`, `en cours`.

## 3. Branches courtes et intégration

`main` reste exécutable. Chaque fonctionnalité vit dans une branche courte nommée `feature/<nom>`.
Avant fusion : un autre membre relit le diff (`git diff`), les tests passent, puis on fusionne avec `--no-ff` et on supprime la branche.

```bash
git switch -c feature/demandes
git add src/ && git commit -m "Ajoute POST /api/demandes"
git switch main
git merge --no-ff feature/demandes -m "Fusionne feature/demandes apres revue"
git branch -d feature/demandes
```

## 4. Conflit : quatre étapes

1. **Constater** les marqueurs `<<<<<<<`, `=======`, `>>>>>>>` (voir `preuves/conflit_markers.txt`).
2. **Choisir ou combiner** le contenu des deux versions, ici la ligne 3 du README.
3. **Supprimer tous les marqueurs** et enregistrer le fichier.
4. **Tester puis valider** : `php tests/test_connexion.php`, `git add README.md`, `git commit -m "Resout le conflit du README et relance les tests"`.

Ne jamais résoudre un conflit sans relancer les tests.

## 5. Preuves et audit

- `git log --oneline --graph --all --decorate` : structure de l'historique et branches.
- `git show <sha>` : détail d'un commit (fichiers, lignes, message).
- `git diff <sha1> <sha2>` : comparaison entre deux états, utilisée en revue.

Chaque membre doit pouvoir expliquer oralement un de ses commits : voir `preuves/explications_orales.md`.
