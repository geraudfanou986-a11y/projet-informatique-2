# Rétrospective

Trois questions, une réponse chacune (section 10.5). À relire et à signer en fin de
soutenance par les trois membres.

## Qu'est-ce qui a bien fonctionné ?

**La matrice de tests exécutée, pas seulement écrite.** Le script `TP08/scripts/tests_tp8.php`
envoie de vraies requêtes HTTP avec une session par rôle et écrit 32 résultats horodatés
dans `preuves/resultats_tests.txt` et `.json`. Le premier passage a donné 30/32 : au
lieu d'ajuster l'attendu, nous avons corrigé le vrai défaut (`utilisateur.id` au lieu
de `etudiant.id`) et rejoué la matrice jusqu'à 32/32. C'est cette boucle « test → bug →
correctif → re-test » qui a rendu la démonstration sûre.

## Qu'est-ce qui a été difficile ?

**Le cloisonnement des données entre étudiants.** Le contrôle d'accès semblait correct
dans le contrôleur, mais les cas 21 et 26 échouaient parce que l'identifiant du compte
utilisateur était utilisé pour retrouver la fiche étudiant. Le diagnostic a demandé de
suivre la session HTTP complète (deux navigateurs, deux `PHPSESSID`) puis de comparer
les identifiants en base. La leçon : tester avec **deux vrais rôles distincts**, pas
seulement avec un compte administrateur. Le déploiement a aussi réservé une surprise
avec `mysqldump` et le compte limité (`TP09/docs/incident.md`).

## Que change-t-on pour la version 2 ?

**Reprendre les éléments exclus du périmètre v1 et réduire la dette technique.**
La v2 ajoutera les notifications (e-mail puis SMS), le portail entreprise et la
convention signée électroniquement, qui étaient explicitement reportés dans le README.
Avant cela, la dette technique sera traitée : migrations SQL versionnées remplaçant
les scripts d'import successifs, couverture automatisée des cas limites à chaque
commit et journalisation des accès refusés pour mieux diagnostiquer les problèmes
d'autorisation. Le principe « pas de root, données fictives, preuve pour chaque
exigence » reste inchangé.

---

Signatures (à compléter le jour de la soutenance) :

- Ange : ______________________
- Vanessa : ______________________
- Géraud : ______________________
