/* Page d'accueil / connexion / inscription. */
(() => {
  'use strict';

  const onglets = [...document.querySelectorAll('[role="tab"]')];
  const panneaux = {
    connexion: document.getElementById('panneau-connexion'),
    inscription: document.getElementById('panneau-inscription')
  };
  const erreur = document.getElementById('erreur-form');
  const boutons = document.querySelectorAll('button[type="submit"]');

  function afficherErreur(message) {
    erreur.textContent = message || '';
    erreur.classList.toggle('visible', Boolean(message));
  }

  function activerOnglet(nom, focus = false) {
    onglets.forEach((tab) => {
      const actif = tab.dataset.panneau === nom;
      tab.setAttribute('aria-selected', String(actif));
      tab.tabIndex = actif ? 0 : -1;
      if (actif && focus) tab.focus();
    });
    Object.entries(panneaux).forEach(([cle, panneau]) => {
      panneau.hidden = cle !== nom;
    });
    afficherErreur('');
    history.replaceState(null, '', location.pathname + (nom === 'inscription' ? '?action=inscription' : ''));
  }

  onglets.forEach((tab, index) => {
    tab.addEventListener('click', () => activerOnglet(tab.dataset.panneau));
    tab.addEventListener('keydown', (e) => {
      if (!['ArrowLeft', 'ArrowRight'].includes(e.key)) return;
      e.preventDefault();
      const suivant = onglets[(index + (e.key === 'ArrowRight' ? 1 : onglets.length - 1)) % onglets.length];
      activerOnglet(suivant.dataset.panneau, true);
    });
  });

  // Boutons « compte de démonstration » : remplissage en un clic.
  document.querySelectorAll('.compte').forEach((bouton) => {
    bouton.addEventListener('click', () => {
      activerOnglet('connexion');
      document.getElementById('co-email').value = bouton.dataset.email;
      document.getElementById('co-mot-de-passe').value = bouton.dataset.motDePasse;
      document.getElementById('co-email').focus();
    });
  });

  document.getElementById('form-connexion').addEventListener('submit', async (e) => {
    e.preventDefault();
    afficherErreur('');
    boutons.forEach((b) => { b.disabled = true; });
    try {
      const resultat = await Api.connexion(
        document.getElementById('co-email').value.trim(),
        document.getElementById('co-mot-de-passe').value
      );
      Api.viderJeton();
      if (resultat.csrf) sessionStorage.setItem('stageflow_csrf', resultat.csrf);
      location.href = 'app.html';
    } catch (err) {
      afficherErreur(err.message);
      boutons.forEach((b) => { b.disabled = false; });
    }
  });

  document.getElementById('form-inscription').addEventListener('submit', async (e) => {
    e.preventDefault();
    afficherErreur('');
    const motDePasse = document.getElementById('in-mot-de-passe').value;
    const confirmation = document.getElementById('in-confirmation').value;
    if (motDePasse !== confirmation) {
      afficherErreur('Les deux mots de passe ne correspondent pas.');
      return;
    }
    boutons.forEach((b) => { b.disabled = true; });
    try {
      const resultat = await Api.inscription({
        nom: document.getElementById('in-nom').value.trim(),
        prenom: document.getElementById('in-prenom').value.trim(),
        email: document.getElementById('in-email').value.trim(),
        motDePasse,
        role: document.getElementById('in-role').value
      });
      Api.viderJeton();
      if (resultat.csrf) sessionStorage.setItem('stageflow_csrf', resultat.csrf);
      location.href = 'app.html';
    } catch (err) {
      afficherErreur(err.message);
      boutons.forEach((b) => { b.disabled = false; });
    }
  });

  if (new URLSearchParams(location.search).get('raison') === 'session') {
    activerOnglet('connexion');
    afficherErreur('Votre session a expiré. Veuillez vous reconnecter.');
  } else {
    activerOnglet(new URLSearchParams(location.search).get('action') === 'inscription' ? 'inscription' : 'connexion');
  }
})();
