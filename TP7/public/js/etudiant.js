'use strict';

const corpsTableau = document.getElementById('corps-tableau-etudiant');
const nomsEntreprises = new Map();
const statutsConnus = new Map();
let demandeSelectionnee = null;

function libelleEntreprise(demande) {
  const nom = nomsEntreprises.get(Number(demande.entreprise_id));
  return nom ? `${nom} (n° ${demande.entreprise_id})` : `Entreprise n° ${demande.entreprise_id}`;
}

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

  const premiereLecture = statutsConnus.size === 0;
  const changements = [];

  demandes.forEach((demande) => {
    const ligne = document.createElement('tr');

    const celluleId = document.createElement('td');
    celluleId.textContent = String(demande.id);

    const celluleEntreprise = document.createElement('td');
    celluleEntreprise.textContent = libelleEntreprise(demande);

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

    // Un changement de statut détecté par l'actualisation automatique est visible.
    const avant = statutsConnus.get(demande.id);
    if (!premiereLecture && avant !== undefined && avant !== demande.statut) {
      ligne.classList.add('ligne-modifiee');
      changements.push(`Votre demande n° ${demande.id} est passée en ${demande.statut}.`);
    }
    statutsConnus.set(demande.id, demande.statut);

    corpsTableau.appendChild(ligne);
  });

  if (changements.length) {
    afficherNotification('notification-etudiant', changements.join(' '));
  }

  // Le détail ouvert reste synchronisé avec la base.
  if (demandeSelectionnee !== null) {
    const miseAJour = demandes.find((d) => d.id === demandeSelectionnee);
    if (miseAJour) {
      afficherDetail(miseAJour);
    }
  }
}

/** Quatrième écran du parcours étudiant : le détail d'une demande. */
function afficherDetail(demande) {
  demandeSelectionnee = demande.id;
  document.getElementById('detail-vide').hidden = true;
  document.getElementById('tableau-detail').hidden = false;
  document.getElementById('detail-id').textContent = String(demande.id);
  document.getElementById('detail-entreprise').textContent = libelleEntreprise(demande);
  document.getElementById('detail-statut').textContent = String(demande.statut);
  document.getElementById('detail-motif').textContent = demande.motif_refus
    ? String(demande.motif_refus)
    : 'Aucun motif';
}

async function chargerListe() {
  try {
    const demandes = await appelerApi('/api/demandes/mes');
    rendreListe(demandes);
    marquerMiseAJour('maj-etudiant');
  } catch (e) {
    afficherErreur('erreur-demande', messageErreur(e));
    rendreListe([]);
  }
}

async function chargerEntreprises() {
  const select = document.getElementById('entreprise-id');
  try {
    const entreprises = await appelerApi('/api/entreprises');
    select.replaceChildren();
    const invitation = document.createElement('option');
    invitation.value = '';
    invitation.textContent = '— Choisir une entreprise —';
    select.appendChild(invitation);
    entreprises.forEach((entreprise) => {
      nomsEntreprises.set(Number(entreprise.id), entreprise.nom);
      const option = document.createElement('option');
      option.value = String(entreprise.id);
      option.textContent = entreprise.ville
        ? `${entreprise.nom} — ${entreprise.ville}`
        : entreprise.nom;
      select.appendChild(option);
    });
  } catch (e) {
    afficherErreur('erreur-entreprise', messageErreur(e));
  }
}

document.getElementById('formulaire-demande').addEventListener('submit', async (evenement) => {
  evenement.preventDefault();
  effacerMessages(['erreur-entreprise', 'erreur-demande', 'message-demande']);

  const entrepriseId = Number(document.getElementById('entreprise-id').value);

  if (!Number.isInteger(entrepriseId) || entrepriseId < 1) {
    afficherErreur('erreur-entreprise', 'Choisissez une entreprise dans la liste.');
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

chargerProfil();
chargerEntreprises().then(chargerListe);
installerActualisationAuto({ checkboxId: 'auto-etudiant', charger: chargerListe });
