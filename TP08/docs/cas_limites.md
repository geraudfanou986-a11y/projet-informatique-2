# TP08 — Les quatre cas limites exigés

Le TP08 demande d'**exécuter** quatre cas limites : demande inexistante, refus sans
motif, validation répétée, date invalide. Chacun est un cas de la matrice
(`docs/matrice_tests.md`, cas n° 14, 15, 17 et 18) et une requête HTTP réelle.
Pour chaque cas : le **attendu** (écrit avant), l'**observé**, la **preuve** et
l'**analyse**.

---

## T-01 — Demande inexistante

| | |
|---|---|
| **Exigence** | EF-03 — une décision ne porte que sur une demande existante |
| **Données** | `PATCH /api/demandes/999999/statut` corps `{"statut":"VALIDEE"}` |
| **Attendu (avant)** | `404` et aucune écriture |
| **Observé** | `HTTP 404 {"erreur":"Demande introuvable"}` |
| **Preuve** | `preuves/resultats_tests.txt` ligne 24 |
| **Verdict** | **Conforme** |

**Analyse.** La demande est cherchée dans la transaction avant tout `UPDATE`
(`DemandeRepository::findById()` renvoie `null` → `HttpException(404)` levée, la
transaction est annulée). Le code ne devine pas : il ne crée pas de ligne fantôme et
ne renvoie pas `200` avec un objet vide. Le choix du code `404` (et non `400`) est
correct : la ressource visée n'existe pas.

---

## T-02 — Refus sans motif

| | |
|---|---|
| **Exigence** | EF-03 — un refus est toujours motivé (règle métier) |
| **Données** | `PATCH /api/demandes/13/statut` corps `{"statut":"REFUSEE"}` (pas de motif) |
| **Attendu (avant)** | `400` |
| **Observé** | `HTTP 400 {"erreur":"Motif obligatoire pour un refus"}` |
| **Preuve** | `preuves/resultats_tests.txt` ligne 25 |
| **Verdict** | **Conforme** |

**Analyse.** Le contrôle est fait dans la couche **métier**
(`DemandeService::changerStatut()`), avant la transaction :

```php
$motif = is_string($motif) && trim($motif) !== '' ? trim($motif) : null;
if ($statut === 'REFUSEE' && $motif === null) {
    throw new HttpException(400, 'Motif obligatoire pour un refus');
}
```

Un motif composé uniquement d'espaces est traité comme absent (`trim()`), ce qui
évite un refus « vide en apparence ». La base porte en plus une contrainte
`CHECK (statut <> 'REFUSEE' OR motif_refus IS NOT NULL)` : même une écriture
directe en SQL ne pourrait pas produire un refus sans motif (défense en
profondeur).

---

## T-03 — Validation répétée

| | |
|---|---|
| **Exigence** | EF-03 — les transitions de statut sont strictement encadrées |
| **Données** | `PATCH /api/demandes/13/statut {VALIDEE}` une **seconde** fois, après une première validation réussie |
| **Attendu (avant)** | `409` et statut inchangé (`VALIDEE`) |
| **Observé** | `HTTP 409 {"erreur":"Transition interdite"}` puis statut relu en base : `VALIDEE` |
| **Preuve** | `preuves/resultats_tests.txt` ligne 27 |
| **Verdict** | **Conforme** |

**Analyse.** La table des transitions autorisées ne contient aucune sortie pour
`VALIDEE` :

```php
private const TRANSITIONS = [
    'BROUILLON' => ['SOUMISE'],
    'SOUMISE'   => ['VALIDEE', 'REFUSEE'],
    'REFUSEE'   => ['BROUILLON'],
    'VALIDEE'   => [],
];
```

`VALIDEE → VALIDEE` n'est donc pas une transition permise → `409`. Le test ne se
contente pas du code HTTP : il **relit la demande** (`GET /api/demandes/13`) après
le refus et vérifie que le statut est resté `VALIDEE`. C'est le point important :
une double validation ne doit pas rejouer l'historique ni écraser une décision.

---

## T-04 — Date de soutenance invalide

| | |
|---|---|
| **Exigence** | EF-05 — la soutenance se programme sur une date réelle |
| **Données** | `PATCH /api/demandes/13/soutenance` corps `{"dateHeure":"2026-02-30 10:00","salle":"B12"}` |
| **Attendu (avant)** | `400` (date impossible au calendrier) |
| **Observé** | `HTTP 400 {"erreur":"Date de soutenance invalide"}` |
| **Preuve** | `preuves/resultats_tests.txt` ligne 28 |
| **Verdict** | **Conforme** |

**Analyse.** `2026-02-30` a le bon **format** mais n'existe pas au calendrier.
Une simple expression régulière ou un `strtotime()` naïf laisserait passer (PHP
reporte au 2 mars). Le service refuse en comparant la date reformatée à la saisie :

```php
$objet = DateTime::createFromFormat('Y-m-d H:i', $dateHeure);
if ($objet !== false && $objet->format('Y-m-d H:i') === $dateHeure) { /* accepté */ }
```

Le reformatage « 2026-02-30 » → « 2026-03-02 » diffère de l'entrée, donc la date est
rejetée. Le test vérifie aussi la borne positive : la date valide `2026-12-11 10:00`
est acceptée et **enregistrée** en base (cas n° 19, `date_heure = 2026-12-11 10:00:00`).
Un dernier garde-fou métier refuse en `409` de programmer une soutenance sur une
demande qui n'est pas `VALIDEE` (cas n° 20).

---

## Synthèse des quatre cas

| Cas | Entrée | Code attendu | Code observé | Écriture en base |
|---|---|---|---|---|
| T-01 demande inexistante | id `999999` | `404` | `404` | aucune |
| T-02 refus sans motif | `REFUSEE`, motif vide | `400` | `400` | aucune |
| T-03 validation répétée | `VALIDEE` sur `VALIDEE` | `409` | `409` | statut inchangé |
| T-04 date invalide | `2026-02-30 10:00` | `400` | `400` | aucune |

Les quatre cas produisent le code attendu **et** n'écrivent rien d'incorrect en
base : c'est le comportement visé par le TP.
