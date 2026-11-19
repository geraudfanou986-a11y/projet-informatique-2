'use strict';

/** Petites fonctions partagées par les trois pages. */

function afficherErreur(id, message) {
  const zone = document.getElementById(id);
  if (zone) {
    zone.textContent = message || '';
  }
}

function effacerMessages(ids) {
  ids.forEach((id) => afficherErreur(id, ''));
}

function afficherSucces(id, message) {
  const zone = document.getElementById(id);
  if (zone) {
    zone.textContent = message;
  }
}

/**
 * Rendu sécurisé : textContent uniquement.
 * Aucune donnée venue de l'API ou saisie par l'utilisateur n'est passée à innerHTML.
 */
function ajouterTexteDansListe(idListe, texte) {
  const liste = document.getElementById(idListe);
  const element = document.createElement('li');
  element.textContent = texte;
  liste.appendChild(element);
}

function badgeStatut(statut) {
  const badge = document.createElement('span');
  badge.className = 'badge';
  if (statut === 'VALIDEE') badge.classList.add('validee');
  if (statut === 'REFUSEE') badge.classList.add('refusee');
  badge.textContent = statut;
  return badge;
}

async function deconnecter() {
  try {
    await appelerApi('/api/deconnexion', { method: 'POST' });
  } catch (e) {
    // Même en cas d'erreur on revient à l'écran de connexion.
  }
  window.location.href = 'connexion.html';
}
