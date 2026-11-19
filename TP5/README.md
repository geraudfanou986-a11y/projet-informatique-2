# TP5 - Dépôt Git et organisation d'équipe

Projet informatique 2 · L3 SIL · flux de travail Git avec branches, revue et conflit résolu.

## À remettre

| Élément | Fichier |
|---|---|
| Dépôt avec historique | `preuves/git-log.txt` (sortie réelle de `git log --oneline --graph --all --decorate`) |
| Capture du Kanban | `kanban.md`, `kanban.html` et `preuves/kanban.png` si disponible |
| Sortie de `git log --graph` | `preuves/git-log.txt` |
| Conflit résolu et testé | `conflit_resolution.md` + `preuves/conflit_markers.txt` |
| Contribution de chacun | `preuves/explications_orales.md` |

## Organisation

- `main` reste toujours exécutable : aucune modification directe, aucune fusion sans revue.
- Chaque fonctionnalité vit dans une branche courte (`feature/connexion`, `feature/demandes`).
- Une carte Kanban dure moins d'une séance ; sinon elle est découpée.
- Le conflit est simulé sur la ligne 3 du README, puis résolu et testé.

## Commandes essentielles

```bash
git init -b main
git add .gitignore README.md
git commit -m "Initialise le depot et le README"

git switch -c feature/connexion
git add src/ && git commit -m "Ajoute la connexion PDO"
git switch main
git merge --no-ff feature/connexion -m "Fusionne feature/connexion apres revue"
git branch -d feature/connexion

git log --oneline --graph --all --decorate
git show <sha>
git diff <sha1> <sha2>
```

## Résultat de la simulation

L'historique généré (`preuves/git-log.txt`) montre :

- un premier commit sur `main` ;
- deux branches de fonctionnalité fusionnées après revue (`--no-ff`) ;
- deux branches `feature/readme-a` et `feature/readme-b` modifiant la même ligne ;
- un commit final de résolution de conflit sur `main`.

Voir [`conventions-git.md`](conventions-git.md), [`kanban.md`](kanban.md) et [`conflit_resolution.md`](conflit_resolution.md).
