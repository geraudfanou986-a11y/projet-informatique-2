# Conflit simulé sur la ligne 3 du README

## Scénario

Deux branches partent de `main` et modifient la même ligne du README :

- `feature/readme-a` (Membre 1) : « Titre version A (proposé par Membre 1) »
- `feature/readme-b` (Membre 2) : « Titre version B (proposé par Membre 2) »

Après fusion de `feature/readme-a` dans `main`, la fusion de `feature/readme-b` produit un conflit.
Le contenu observé est conservé dans `preuves/conflit_markers.txt`.

## Marqueurs observés (extrait)

```
<<<<<<< HEAD
Titre version A (propose par Membre 1)
=======
Titre version B (propose par Membre 2)
>>>>>>> feature/readme-b
```

## Résolution appliquée

1. Les deux propositions sont lues ; on retient une formulation unique et claire.
2. Les trois lignes de marqueurs sont supprimées.
3. La ligne retenue devient : `Projet informatique 2 - gestion des stages (titre retenu apres revue)`.
4. Les tests sont relancés (`php tests/test_connexion.php` ou, à défaut de base, la vérification de syntaxe PHP), puis :

```bash
git add README.md
git commit -m "Resout le conflit du README et relance les tests"
```

## Preuve

Le commit de résolution apparaît en tête de `preuves/git-log.txt` :

```
*   5b4bbea (HEAD -> main) Resout le conflit du README et relance les tests
|\
| * 8495179 (feature/readme-b) Propose le titre B du README
* | 6fe23b1 (feature/readme-a) Propose le titre A du README
|/
```

Le conflit est résolu **et testé**, et la contribution des deux membres reste visible dans l'historique.
