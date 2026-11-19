'use strict';

/** Petites fonctions partagées par les pages du TP7. */

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

/** Bandeau de notification discret, utilisé quand une liste change toute seule. */
function afficherNotification(id, message) {
  const zone = document.getElementById(id);
  if (!zone || !message) {
    return;
  }
  zone.textContent = message;
  zone.hidden = false;
}

function marquerMiseAJour(id) {
  const zone = document.getElementById(id);
  if (zone) {
    zone.textContent = 'Données lues en base à ' + new Date().toLocaleTimeString('fr-FR') + '.';
  }
}

/** Actualisation automatique : la liste suit les décisions prises dans un autre onglet. */
function installerActualisationAuto({ intervalleMs = 15000, checkboxId, charger }) {
  const caseAuto = document.getElementById(checkboxId);

  async function cycle() {
    if (document.hidden) {
      return;
    }
    await charger();
  }

  function demarrer() {
    arreter();
    minuteur = window.setInterval(cycle, intervalleMs);
  }

  function arreter() {
    if (minuteur !== null) {
      window.clearInterval(minuteur);
      minuteur = null;
    }
  }

  let minuteur = null;
  if (caseAuto) {
    caseAuto.addEventListener('change', () => (caseAuto.checked ? demarrer() : arreter()));
    if (caseAuto.checked) {
      demarrer();
    }
  } else {
    demarrer();
  }

  // Dès que l'on revient sur l'onglet, on relit la base immédiatement.
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      cycle();
    }
  });
}

const LIBELLES_ROLES = {
  etudiant: 'Étudiant',
  responsable: 'Responsable',
  secretariat: 'Secrétariat',
};

/** Affiche qui est connecté dans l'en-tête (identifiant fourni par le serveur). */
async function chargerProfil() {
  try {
    const profil = await appelerApi('/api/moi');
    const cible = document.getElementById('profil-utilisateur');
    if (cible) {
      cible.textContent = profil.email + ' · ' + (LIBELLES_ROLES[profil.role] || profil.role);
    }
  } catch (e) {
    // L'appelerApi gère déjà la redirection en cas de session expirée.
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
  if (statut === 'SOUMISE') badge.classList.add('soumise');
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
