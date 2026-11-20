/* Couche HTTP unique : CSRF, sessions, erreurs et déconnexion automatique. */
window.Api = (() => {
  'use strict';

  let jeton = sessionStorage.getItem('stageflow_csrf') || '';
  let nouvelleTentativeCsrf = false;

  const publiques = ['/api/connexion', '/api/inscription', '/api/csrf'];

  async function lireReponse(reponse) {
    const texte = await reponse.text();
    if (!texte) return {};
    try {
      return JSON.parse(texte);
    } catch (_) {
      return { erreur: 'Réponse inattendue du serveur' };
    }
  }

  async function demanderJeton() {
    const reponse = await fetch('/api/csrf', { credentials: 'same-origin' });
    const donnees = await lireReponse(reponse);
    if (!reponse.ok || !donnees.csrf) {
      throw new Error(donnees.erreur || 'Impossible d’obtenir le jeton de sécurité');
    }
    jeton = donnees.csrf;
    sessionStorage.setItem('stageflow_csrf', jeton);
    return jeton;
  }

  async function request(method, chemin, corps) {
    const options = {
      method,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    };

    if (corps !== undefined) {
      if (corps instanceof FormData) {
        // Le navigateur fixe lui-même la frontière multipart : ne pas mettre de Content-Type.
        options.body = corps;
      } else {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(corps);
      }
    }
    if (!publiques.includes(chemin) && method !== 'GET') {
      if (!jeton) await demanderJeton();
      options.headers['X-CSRF-Token'] = jeton;
    }

    let reponse = await fetch(chemin, options);
    let donnees = await lireReponse(reponse);

    // Un jeton périmé est renouvelé une seule fois, puis la requête est rejouée.
    if (reponse.status === 403 && /csrf|s.curit/i.test(donnees.erreur || '') && !nouvelleTentativeCsrf) {
      nouvelleTentativeCsrf = true;
      jeton = '';
      sessionStorage.removeItem('stageflow_csrf');
      await demanderJeton();
      reponse = await fetch(chemin, { ...options, headers: { ...options.headers, 'X-CSRF-Token': jeton } });
      donnees = await lireReponse(reponse);
      nouvelleTentativeCsrf = false;
    }

    if (reponse.status === 401 && !location.pathname.endsWith('index.html') && location.pathname !== '/') {
      sessionStorage.clear();
      location.href = 'index.html?raison=session';
      throw new Error('Session expirée');
    }

    if (!reponse.ok) {
      const erreur = new Error(donnees.erreur || `Erreur ${reponse.status}`);
      erreur.statut = reponse.status;
      erreur.donnees = donnees;
      throw erreur;
    }

    return donnees;
  }

  return {
    get: (chemin) => request('GET', chemin),
    post: (chemin, corps) => request('POST', chemin, corps),
    patch: (chemin, corps) => request('PATCH', chemin, corps),
    upload: (chemin, donnees) => request('POST', chemin, donnees),
    supprimer: (chemin) => request('DELETE', chemin),
    connexion: (email, motDePasse) => request('POST', '/api/connexion', { email, motDePasse }),
    inscription: (donnees) => request('POST', '/api/inscription', donnees),
    deconnexion: () => request('POST', '/api/deconnexion', {}),
    moi: () => request('GET', '/api/moi'),
    sante: () => request('GET', '/api/sante'),
    viderJeton: () => { jeton = ''; sessionStorage.removeItem('stageflow_csrf'); }
  };
})();
