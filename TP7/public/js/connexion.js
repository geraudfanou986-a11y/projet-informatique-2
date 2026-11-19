'use strict';

// Message affiché quand on revient après une session expirée (401).
const raison = new URLSearchParams(window.location.search).get('raison');
if (raison === 'session') {
  afficherErreur('raison-session', 'Votre session a expiré. Reconnectez-vous pour continuer.');
}

document.getElementById('formulaire-connexion').addEventListener('submit', async (evenement) => {
  evenement.preventDefault();
  effacerMessages(['erreur-email', 'erreur-mot-de-passe', 'erreur-generale', 'message-connexion', 'raison-session']);

  const email = document.getElementById('email').value.trim();
  const motDePasse = document.getElementById('mot-de-passe').value;

  // Contrôles côté navigateur : ergonomie uniquement, le serveur revérifie tout.
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
    afficherSucces('message-connexion', 'Connexion réussie, redirection…');
    const page = resultat.role === 'etudiant' ? 'etudiant.html' : 'responsable.html';
    window.location.href = page;
  } catch (e) {
    const message = messageErreur(e);
    if (e.statut === 400 || e.statut === 401) {
      // 400 et 401 : message placé près des champs concernés.
      afficherErreur('erreur-email', message);
      afficherErreur('erreur-mot-de-passe', message);
    } else {
      // 500 et erreurs réseau : message neutre, jamais de page blanche.
      afficherErreur('erreur-generale', message);
    }
  }
});
