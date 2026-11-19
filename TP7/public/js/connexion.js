'use strict';

const parametres = new URLSearchParams(window.location.search);

// Message affiché quand on revient après une session expirée (401).
if (parametres.get('raison') === 'session') {
  afficherErreur('raison-session', 'Votre session a expiré. Reconnectez-vous pour continuer.');
}

/** Bascule entre les deux panneaux sans recharger la page. */
function basculerPanneau(actif) {
  const inscription = actif === 'inscription';
  document.getElementById('panneau-connexion').hidden = inscription;
  document.getElementById('panneau-inscription').hidden = !inscription;

  const ongletConnexion = document.getElementById('onglet-connexion');
  const ongletInscription = document.getElementById('onglet-inscription');
  ongletConnexion.classList.toggle('actif', !inscription);
  ongletInscription.classList.toggle('actif', inscription);
  ongletConnexion.setAttribute('aria-selected', String(!inscription));
  ongletInscription.setAttribute('aria-selected', String(inscription));
}

document.getElementById('onglet-connexion').addEventListener('click', () => basculerPanneau('connexion'));
document.getElementById('onglet-inscription').addEventListener('click', () => basculerPanneau('inscription'));

if (parametres.get('action') === 'inscription') {
  basculerPanneau('inscription');
}

function redirigerSelonRole(role) {
  afficherSucces(
    role === 'etudiant' ? 'message-connexion' : 'message-inscription',
    'Connexion réussie, redirection…'
  );
  window.location.href = role === 'etudiant' ? 'etudiant.html' : 'responsable.html';
}

// ---------------------------------------------------------------------------
// Connexion
// ---------------------------------------------------------------------------
document.getElementById('formulaire-connexion').addEventListener('submit', async (evenement) => {
  evenement.preventDefault();
  effacerMessages(['erreur-email', 'erreur-mot-de-passe', 'erreur-generale', 'message-connexion', 'raison-session']);

  const email = document.getElementById('email').value.trim();
  const motDePasse = document.getElementById('mot-de-passe').value;

  let champEnFaute = null;
  if (!email || !email.includes('@')) {
    afficherErreur('erreur-email', 'Saisissez une adresse email valide.');
    champEnFaute = 'email';
  }
  if (!motDePasse) {
    afficherErreur('erreur-mot-de-passe', 'Saisissez votre mot de passe.');
    champEnFaute = champEnFaute || 'mot-de-passe';
  }
  if (champEnFaute) {
    document.getElementById(champEnFaute).focus();
    return;
  }

  try {
    const resultat = await appelerApi('/api/connexion', {
      method: 'POST',
      corps: { email, motDePasse },
    });
    redirigerSelonRole(resultat.role);
  } catch (e) {
    const message = messageErreur(e);
    if (e.statut === 400 || e.statut === 401) {
      // Message placé près des champs : on reste sur la page de connexion.
      afficherErreur('erreur-email', message);
      afficherErreur('erreur-mot-de-passe', message);
    } else {
      // 500 et erreurs réseau : message neutre, jamais de page blanche.
      afficherErreur('erreur-generale', message);
    }
  }
});

// ---------------------------------------------------------------------------
// Inscription (n'importe quel visiteur)
// ---------------------------------------------------------------------------
const zonesInscription = [
  'erreur-ins-nom', 'erreur-ins-prenom', 'erreur-ins-email', 'erreur-ins-role',
  'erreur-ins-mot-de-passe', 'erreur-ins-confirmation', 'erreur-ins-generale',
  'message-inscription',
];

document.getElementById('formulaire-inscription').addEventListener('submit', async (evenement) => {
  evenement.preventDefault();
  effacerMessages(zonesInscription);

  const nom = document.getElementById('ins-nom').value.trim();
  const prenom = document.getElementById('ins-prenom').value.trim();
  const email = document.getElementById('ins-email').value.trim();
  const role = document.getElementById('ins-role').value;
  const motDePasse = document.getElementById('ins-mot-de-passe').value;
  const confirmation = document.getElementById('ins-confirmation').value;

  let champEnFaute = null;
  if (nom.length < 2) {
    afficherErreur('erreur-ins-nom', 'Saisissez votre nom.');
    champEnFaute = 'ins-nom';
  }
  if (prenom.length < 2) {
    afficherErreur('erreur-ins-prenom', 'Saisissez votre prénom.');
    champEnFaute = champEnFaute || 'ins-prenom';
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    afficherErreur('erreur-ins-email', 'Saisissez une adresse email valide.');
    champEnFaute = champEnFaute || 'ins-email';
  }
  if (!role) {
    afficherErreur('erreur-ins-role', 'Choisissez le type de compte.');
    champEnFaute = champEnFaute || 'ins-role';
  }
  if (motDePasse.length < 8) {
    afficherErreur('erreur-ins-mot-de-passe', 'Le mot de passe doit contenir au moins 8 caractères.');
    champEnFaute = champEnFaute || 'ins-mot-de-passe';
  }
  if (confirmation !== motDePasse) {
    afficherErreur('erreur-ins-confirmation', 'Les deux mots de passe ne correspondent pas.');
    champEnFaute = champEnFaute || 'ins-confirmation';
  }
  if (champEnFaute) {
    document.getElementById(champEnFaute).focus();
    return;
  }

  try {
    const compte = await appelerApi('/api/inscription', {
      method: 'POST',
      corps: { nom, prenom, email, role, motDePasse },
    });
    afficherSucces('message-inscription', compte.message + ' Redirection…');
    redirigerSelonRole(compte.role);
  } catch (e) {
    const message = messageErreur(e);
    if (e.statut === 409) {
      afficherErreur('erreur-ins-email', message);
    } else if (e.statut === 400) {
      // Le serveur donne le message précis : on le place près du champ concerné.
      if (/email/i.test(message)) afficherErreur('erreur-ins-email', message);
      else if (/mot de passe/i.test(message)) afficherErreur('erreur-ins-mot-de-passe', message);
      else if (/rôle/i.test(message)) afficherErreur('erreur-ins-role', message);
      else if (/prénom/i.test(message)) afficherErreur('erreur-ins-prenom', message);
      else if (/nom/i.test(message)) afficherErreur('erreur-ins-nom', message);
      else afficherErreur('erreur-ins-generale', message);
    } else {
      afficherErreur('erreur-ins-generale', message);
    }
  }
});
