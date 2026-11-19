'use strict';

/**
 * Couche d'appel API unique pour toutes les pages du TP7.
 * - les erreurs sont renvoyées sous forme de messages lisibles ;
 * - un 401 renvoie vers la connexion (aucune page blanche) ;
 * - le détail technique d'un 500 n'est jamais affiché ;
 * - toute écriture porte l'en-tête X-CSRF-Token exigé par le serveur.
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

const METHODES_SANS_ECRITURE = new Set(['GET', 'HEAD', 'OPTIONS']);
// Avant la connexion il n'existe pas encore de session : ces deux routes sont exemptées de jeton.
const CHEMINS_SANS_CSRF = new Set(['/api/connexion', '/api/inscription', '/api/csrf']);

/** Jeton CSRF de la session, mémorisé dès qu'une réponse le fournit. */
let jetonCsrf = null;

function memoriserCsrf(donnees) {
  if (donnees && typeof donnees.csrf === 'string' && donnees.csrf !== '') {
    jetonCsrf = donnees.csrf;
  }
}

async function recupererJetonCsrf() {
  try {
    const reponse = await fetch('/api/csrf', { credentials: 'same-origin' });
    if (reponse.ok) {
      memoriserCsrf(await reponse.json());
    }
  } catch (e) {
    // Le prochain appel d'écriture renverra une erreur lisible si le serveur est injoignable.
  }
  return jetonCsrf;
}

async function appelerApi(chemin, options = {}, reessai = true) {
  const { corps, ...reste } = options;
  const methode = (reste.method || 'GET').toUpperCase();
  const estEcriture = !METHODES_SANS_ECRITURE.has(methode) && !CHEMINS_SANS_CSRF.has(chemin);

  const entetes = Object.assign({ 'Content-Type': 'application/json' }, reste.headers || {});
  if (estEcriture) {
    if (!jetonCsrf) {
      await recupererJetonCsrf();
    }
    if (jetonCsrf) {
      entetes['X-CSRF-Token'] = jetonCsrf;
    }
  }

  const configuration = {
    method: methode,
    credentials: 'same-origin',               // cookie PHPSESSID envoyé avec fetch
    headers: entetes,
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
  memoriserCsrf(donnees);

  if (!reponse.ok) {
    if (reponse.status === 401) {
      // Un mot de passe erroné renvoie aussi 401 : dans ce cas on reste sur le
      // formulaire pour afficher « Identifiants invalides », pas « session expirée ».
      const surPageConnexion = /(^|\/)connexion\.html$/.test(window.location.pathname);
      const estTentativeConnexion = chemin === '/api/connexion';
      if (!surPageConnexion && !estTentativeConnexion) {
        window.location.href = 'connexion.html?raison=session';
      }
      throw new ErreurApi(
        (donnees && donnees.erreur) || MESSAGES_PAR_DEFAUT[401],
        401,
        donnees
      );
    }

    const message =
      (donnees && (donnees.erreur || donnees.message)) ||
      MESSAGES_PAR_DEFAUT[reponse.status] ||
      `Erreur inattendue (${reponse.status}).`;

    // Jeton expiré ou absent : on le renouvelle une fois puis on rejoue l'écriture.
    if (reponse.status === 403 && estEcriture && reessai && /jeton|csrf/i.test(message)) {
      await recupererJetonCsrf();
      return appelerApi(chemin, options, false);
    }

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
