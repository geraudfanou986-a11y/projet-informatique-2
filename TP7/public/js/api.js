'use strict';

/**
 * Couche d'appel API unique pour toutes les pages du TP7.
 * - les erreurs sont renvoyées sous forme de messages lisibles ;
 * - un 401 renvoie vers la connexion (aucune page blanche) ;
 * - le détail technique d'un 500 n'est jamais affiché.
 */
class ErreurApi extends Error {
  constructor(message, statut = null, donnees = null) {
    super(message);
    this.name = 'ErreurApi';
    this.statut = statut;
    this.donnees = donnees;
  }
}

const MESSAGES_PAR_DEFAUT = {
  400: 'Données invalides. Vérifiez les champs du formulaire.',
  401: 'Votre session a expiré. Veuillez vous reconnecter.',
  403: "Vous n'avez pas le droit d'effectuer cette action.",
  404: 'Élément introuvable.',
  409: 'Cette action est impossible dans l’état actuel de la demande.',
  500: 'Erreur serveur. Veuillez réessayer plus tard.',
};

async function appelerApi(chemin, options = {}) {
  const { corps, ...reste } = options;
  const configuration = {
    method: reste.method || 'GET',
    credentials: 'same-origin',               // cookie PHPSESSID envoyé avec fetch
    headers: Object.assign(
      { 'Content-Type': 'application/json' },
      reste.headers || {}
    ),
  };
  if (corps !== undefined) {
    configuration.body = JSON.stringify(corps);
  }

  let reponse;
  try {
    reponse = await fetch(chemin, configuration);
  } catch (e) {
    throw new ErreurApi('Serveur injoignable. Vérifiez que le serveur PHP est démarré.');
  }

  let donnees = null;
  try {
    donnees = await reponse.json();
  } catch (e) {
    donnees = null;
  }

  if (!reponse.ok) {
    if (reponse.status === 401) {
      window.location.href = 'connexion.html?raison=session';
      throw new ErreurApi(MESSAGES_PAR_DEFAUT[401], 401, donnees);
    }
    const message =
      (donnees && (donnees.erreur || donnees.message)) ||
      MESSAGES_PAR_DEFAUT[reponse.status] ||
      `Erreur inattendue (${reponse.status}).`;
    throw new ErreurApi(message, reponse.status, donnees);
  }

  return donnees;
}

function messageErreur(e) {
  if (e instanceof ErreurApi) {
    return e.message;
  }
  return 'Une erreur inattendue est survenue.';
}
