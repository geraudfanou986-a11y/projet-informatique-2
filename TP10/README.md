# TP10 — Dossier de livraison et soutenance

Assemblage du dossier, scénario de démonstration de **huit minutes**, préparation
individuelle des réponses et rétrospective. La soutenance orale elle-même doit être
menée par le groupe ; tous les supports écrits sont prêts ici.

## Contenu

| Fichier | Rôle |
|---|---|
| `docs/checklist_livraison.md` | Liste de contrôle des **8 éléments** du dossier, avec état et preuve |
| `docs/demo_8_minutes.md` | Scénario minute par minute (0-1, 1-3, 3-5, 5-6, 6-8) et preuve montrée à chaque étape |
| `docs/repetition.md` | Protocole de répétition chronométrée et solution de repli |
| `docs/justifications.md` | Justifications vérifiables : PDO, transaction de validation, contrôle d'accès serveur |
| `docs/questions_membres.md` | Pour Ange, Vanessa et Géraud : une règle métier, une requête, un test et un commit |
| `docs/retrospective.md` | Un point positif, une difficulté, un changement pour la v2 |
| `preuves/git_log.txt` | Historique réel des commits, support des questions individuelles |
| `preuves/git_status_avant_livraison.txt` | État du dépôt au moment de l'assemblage du dossier |

## Les huit éléments du dossier

Ils reprennent exactement la section 10.1 du cours : dépôt Git, fiche de cadrage,
exigences et matrice de traçabilité, diagrammes UML, schéma SQL et migrations, plan de
tests et fiche de recette, README d'installation, décisions ADR. Le détail et la preuve
de présence sont dans `docs/checklist_livraison.md`.

## Démonstration

Le scénario suit l'ordre imposé : **contexte → dépôt → validation et programmation →
refus 403 → tests et bilan**. Les preuves montrées sont celles déjà produites dans les
TP précédents (ligne en base, statut `VALIDEE` et soutenance, réponse `403`, matrice
32/32). Aucune preuve n'est inventée pour la soutenance.

## Ce qui reste au groupe

1. Répéter la démonstration au chronomètre (grille dans `docs/repetition.md`) et viser
   **moins de 8 minutes**, avec la solution de repli ouverte dans un autre onglet.
2. Répondre oralement aux questions individuelles en s'appuyant sur
   `docs/questions_membres.md` et sur `preuves/git_log.txt`.
3. Tenir la soutenance, puis relire et signer `docs/retrospective.md`.
