'use strict';

const corpsTableauResponsable = document.getElementById('corps-tableau-responsable');
const statutsConnus = new Map();

function boutonAction(demande) {
  const cellule = document.createElement('td');
  const statut = String(demande.statut);

  if (statut === 'BROUILLON' || statut === 'SOUMISE') {
    const bouton = document.createElement('button');
    bouton.className = 'secondaire';
    bouton.type = 'button';
    bouton.textContent = statut === 'BROUILLON' ? 'Soumettre' : 'Valider';
    bouton.addEventListener('click', () => decisionRapide(demande.id, statut === 'BROUILLON' ? 'SOUMISE' : 'VALIDEE'));
    cellule.appendChild(bouton);
  }

  if (statut === 'SOUMISE') {
    const refus = document.createElement('button');
    refus.className = 'danger';
    refus.type = 'button';
    refus.textContent = 'Refuser';
    refus.style.marginLeft = '6px';
    refus.addEventListener('click', () => preparerRefus(demande.id));
    cellule.appendChild(refus);
  }

  if (statut === 'VALIDEE' || statut === 'REFUSEE') {
    cellule.textContent = 'Aucune action';
    cellule.className = 'vide';
  }

  return cellule;
}

function rendreListeResponsable(demandes) {
  corpsTableauResponsable.replaceChildren();

  if (!Array.isArray(demandes) || demandes.length === 0) {
    const ligne = document.createElement('tr');
    const cellule = document.createElement('td');
    cellule.colSpan = 6;
    cellule.className = 'vide';
    cellule.textContent = 'Aucune demande à traiter.';
    ligne.appendChild(cellule);
    corpsTableauResponsable.appendChild(ligne);
    return;
  }

  const premiereLecture = statutsConnus.size === 0;
  const changements = [];

  demandes.forEach((demande) => {
    const ligne = document.createElement('tr');

    const celluleId = document.createElement('td');
    celluleId.textContent = String(demande.id);

    const celluleEtudiant = document.createElement('td');
    celluleEtudiant.textContent = String(demande.etudiant_id);

    const celluleEntreprise = document.createElement('td');
    celluleEntreprise.textContent = String(demande.entreprise_id);

    const celluleStatut = document.createElement('td');
    celluleStatut.appendChild(badgeStatut(String(demande.statut)));

    const celluleMotif = document.createElement('td');
    celluleMotif.textContent = demande.motif_refus ? String(demande.motif_refus) : '—';

    ligne.append(celluleId, celluleEtudiant, celluleEntreprise, celluleStatut, celluleMotif, boutonAction(demande));

    // Surlignage quand le statut vient de changer (y compris depuis un autre onglet).
    const avant = statutsConnus.get(demande.id);
    if (!premiereLecture && avant !== undefined && avant !== demande.statut) {
      ligne.classList.add('ligne-modifiee');
      changements.push(`Demande n° ${demande.id} : ${avant} → ${demande.statut}.`);
    }
    statutsConnus.set(demande.id, demande.statut);

    corpsTableauResponsable.appendChild(ligne);
  });

  if (changements.length) {
    afficherNotification('notification-responsable', changements.join(' '));
  }
}

async function chargerListeResponsable() {
  try {
    const demandes = await appelerApi('/api/demandes');
    rendreListeResponsable(demandes);
    marquerMiseAJour('maj-responsable');
  } catch (e) {
    afficherErreur('erreur-decision', messageErreur(e));
    rendreListeResponsable([]);
  }
}

/** Une seule fonction d'écriture, utilisée par le formulaire et les actions rapides. */
async function envoyerDecision(id, statut, motif) {
  const corps = { statut };
  if (statut === 'REFUSEE') {
    corps.motif = motif;
  }
  return appelerApi(`/api/demandes/${id}/statut`, { method: 'PATCH', corps });
}

function afficherErreurDecision(e) {
  const message = messageErreur(e);
  if (e.statut === 400 && /motif/i.test(message)) {
    afficherErreur('erreur-motif', message);
  } else if (e.statut === 400) {
    afficherErreur('erreur-statut', message);
  } else {
    // 403, 404, 409, 500, réseau… : message lisible, jamais de page blanche.
    afficherErreur('erreur-decision', message);
  }
}

async function terminerDecision(id, resultat, motif) {
  afficherSucces(
    'message-decision',
    `Demande n° ${id} : statut mis à jour en ${resultat.statut}. La liste a été relue depuis la base.`
  );
  if (motif) {
    // Le motif saisi est rendu comme texte, jamais comme du HTML.
    ajouterTexteDansListe('liste-motifs', `Motif saisi : ${motif}`);
  }
  await chargerListeResponsable();
}

async function decisionRapide(id, statut) {
  effacerMessages(['erreur-decision', 'message-decision']);
  try {
    const resultat = await envoyerDecision(id, statut, '');
    await terminerDecision(id, resultat, '');
  } catch (e) {
    afficherErreurDecision(e);
  }
}

function preparerRefus(id) {
  document.getElementById('demande-id').value = String(id);
  document.getElementById('statut').value = 'REFUSEE';
  afficherSucces('message-decision', `Demande n° ${id} : saisissez le motif puis enregistrez le refus.`);
  document.getElementById('motif').focus();
}

document.getElementById('formulaire-decision').addEventListener('submit', async (evenement) => {
  evenement.preventDefault();
  effacerMessages([
    'erreur-demande-id', 'erreur-statut', 'erreur-motif',
    'erreur-decision', 'message-decision',
  ]);

  const idBrut = document.getElementById('demande-id').value.trim();
  const id = Number(idBrut);
  const statut = document.getElementById('statut').value;
  const motif = document.getElementById('motif').value.trim();

  if (!idBrut || !Number.isInteger(id) || id < 1) {
    afficherErreur('erreur-demande-id', 'Saisissez un numéro de demande valide.');
    return;
  }
  if (!statut) {
    afficherErreur('erreur-statut', 'Choisissez une décision.');
    return;
  }
  // Le motif vide n'est pas bloqué côté navigateur : on laisse l'API répondre 400
  // et on affiche son message près du champ, comme dans le contrat du TP6.

  try {
    const resultat = await envoyerDecision(id, statut, motif);
    await terminerDecision(id, resultat, statut === 'REFUSEE' ? motif : '');
    document.getElementById('statut').value = '';
    document.getElementById('motif').value = '';
  } catch (e) {
    afficherErreurDecision(e);
  }
});

document.getElementById('bouton-rafraichir').addEventListener('click', async () => {
  effacerMessages(['erreur-decision', 'message-decision']);
  await chargerListeResponsable();
});

document.getElementById('bouton-deconnexion').addEventListener('click', deconnecter);

chargerProfil();
chargerListeResponsable();
installerActualisationAuto({ checkboxId: 'auto-responsable', charger: chargerListeResponsable });
