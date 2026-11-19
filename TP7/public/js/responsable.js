'use strict';

const corpsTableauResponsable = document.getElementById('corps-tableau-responsable');

function rendreListeResponsable(demandes) {
  corpsTableauResponsable.replaceChildren();

  if (!Array.isArray(demandes) || demandes.length === 0) {
    const ligne = document.createElement('tr');
    const cellule = document.createElement('td');
    cellule.colSpan = 5;
    cellule.className = 'vide';
    cellule.textContent = 'Aucune demande à traiter.';
    ligne.appendChild(cellule);
    corpsTableauResponsable.appendChild(ligne);
    return;
  }

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

    ligne.append(celluleId, celluleEtudiant, celluleEntreprise, celluleStatut, celluleMotif);
    corpsTableauResponsable.appendChild(ligne);
  });
}

async function chargerListeResponsable() {
  try {
    const demandes = await appelerApi('/api/demandes');
    rendreListeResponsable(demandes);
  } catch (e) {
    afficherErreur('erreur-decision', messageErreur(e));
    rendreListeResponsable([]);
  }
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
  const corps = { statut };
  if (statut === 'REFUSEE') {
    corps.motif = motif;
  }

  try {
    const resultat = await appelerApi(`/api/demandes/${id}/statut`, {
      method: 'PATCH',
      corps,
    });
    afficherSucces(
      'message-decision',
      `Demande n° ${id} : statut mis à jour en ${resultat.statut || statut}.`
    );
    // Le motif saisi est rendu comme texte, jamais comme du HTML.
    if (motif) {
      ajouterTexteDansListe('liste-motifs', `Motif saisi : ${motif}`);
    }
    // Relecture depuis la base après l'écriture.
    await chargerListeResponsable();
  } catch (e) {
    if (e.statut === 400) {
      // Réponse 400 de l'API : message placé près du champ concerné.
      afficherErreur('erreur-motif', messageErreur(e));
    } else if (e.statut === 403 || e.statut === 404 || e.statut === 409) {
      afficherErreur('erreur-decision', messageErreur(e));
    } else {
      // 500, réseau… : message neutre, jamais de page blanche.
      afficherErreur('erreur-decision', messageErreur(e));
    }
  }
});

document.getElementById('bouton-rafraichir').addEventListener('click', async () => {
  effacerMessages(['erreur-decision', 'message-decision']);
  await chargerListeResponsable();
});

document.getElementById('bouton-deconnexion').addEventListener('click', deconnecter);

chargerListeResponsable();
