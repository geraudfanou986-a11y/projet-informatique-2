'use strict';

const corpsTableau = document.getElementById('corps-tableau-etudiant');

/** Remplace tout le contenu du tableau par des nœuds texte (jamais innerHTML). */
function rendreListe(demandes) {
  corpsTableau.replaceChildren();

  if (!Array.isArray(demandes) || demandes.length === 0) {
    const ligne = document.createElement('tr');
    const cellule = document.createElement('td');
    cellule.colSpan = 4;
    cellule.className = 'vide';
    cellule.textContent = 'Aucune demande pour le moment.';
    ligne.appendChild(cellule);
    corpsTableau.appendChild(ligne);
    return;
  }

  demandes.forEach((demande) => {
    const ligne = document.createElement('tr');

    const celluleId = document.createElement('td');
    celluleId.textContent = String(demande.id);

    const celluleEntreprise = document.createElement('td');
    celluleEntreprise.textContent = String(demande.entreprise_id);

    const celluleStatut = document.createElement('td');
    celluleStatut.appendChild(badgeStatut(String(demande.statut)));

    const celluleAction = document.createElement('td');
    const bouton = document.createElement('button');
    bouton.className = 'secondaire';
    bouton.type = 'button';
    bouton.textContent = 'Voir détail';
    bouton.addEventListener('click', () => afficherDetail(demande));
    celluleAction.appendChild(bouton);

    ligne.append(celluleId, celluleEntreprise, celluleStatut, celluleAction);
    corpsTableau.appendChild(ligne);
  });
}

/** Quatrième écran du parcours étudiant : le détail d'une demande. */
function afficherDetail(demande) {
  document.getElementById('detail-vide').hidden = true;
  document.getElementById('tableau-detail').hidden = false;
  document.getElementById('detail-id').textContent = String(demande.id);
  document.getElementById('detail-entreprise').textContent = String(demande.entreprise_id);
  document.getElementById('detail-statut').textContent = String(demande.statut);
  document.getElementById('detail-motif').textContent = demande.motif_refus
    ? String(demande.motif_refus)
    : 'Aucun motif';
}

async function chargerListe() {
  try {
    const demandes = await appelerApi('/api/demandes/mes');
    rendreListe(demandes);
  } catch (e) {
    afficherErreur('erreur-demande', messageErreur(e));
    rendreListe([]);
  }
}

document.getElementById('formulaire-demande').addEventListener('submit', async (evenement) => {
  evenement.preventDefault();
  effacerMessages(['erreur-entreprise', 'erreur-demande', 'message-demande']);

  const valeur = document.getElementById('entreprise-id').value.trim();
  const entrepriseId = Number(valeur);

  if (!valeur || !Number.isInteger(entrepriseId) || entrepriseId < 1) {
    afficherErreur('erreur-entreprise', 'Saisissez un identifiant d’entreprise entier positif.');
    document.getElementById('entreprise-id').focus();
    return;
  }

  try {
    const demande = await appelerApi('/api/demandes', {
      method: 'POST',
      corps: { entrepriseId },
    });
    afficherSucces('message-demande', `Demande n° ${demande.id} créée avec le statut ${demande.statut}.`);
    await chargerListe();
  } catch (e) {
    // 400 : erreur près du champ ; 401 géré par appelerApi ; 500 : message neutre.
    if (e.statut === 400) {
      afficherErreur('erreur-entreprise', messageErreur(e));
    } else {
      afficherErreur('erreur-demande', messageErreur(e));
    }
  }
});

document.getElementById('bouton-rafraichir').addEventListener('click', async () => {
  effacerMessages(['erreur-demande', 'message-demande']);
  await chargerListe();
});

document.getElementById('bouton-test-xss').addEventListener('click', () => {
  const valeur = document.getElementById('saisie-xss').value;
  afficherErreur('erreur-xss', '');
  if (!valeur) {
    afficherErreur('erreur-xss', 'Saisissez une valeur à tester.');
    return;
  }
  // textContent : les balises sont affichées comme du texte, jamais interprétées.
  ajouterTexteDansListe('liste-xss', valeur + '  ← affiché avec textContent');
});

document.getElementById('bouton-deconnexion').addEventListener('click', deconnecter);

chargerListe();
