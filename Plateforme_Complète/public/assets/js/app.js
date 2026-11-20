/* Espace connecté : tableaux de bord, demandes, soutenances, historique, profil. */
(() => {
  'use strict';

  /* ---------- Utilitaires DOM (aucune donnée utilisateur dans innerHTML) ---------- */

  const ICONES = {
    dashboard: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    fichier: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
    calendrier: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
    horloge: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    utilisateurs: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
    profil: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 14 0v1"/>',
    deconnecter: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    recherche: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    cloche: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
    coche: '<path d="m20 6-11 11-5-5"/>',
    croix: '<path d="M18 6 6 18M6 6l12 12"/>',
    fleche: '<path d="m9 18 6-6-6-6"/>',
    etincelle: '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/>',
    bouclier: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    entreprise: '<path d="M3 21h18M5 21V7l7-4v18M19 21V11l-7-4M9 9v1M9 13v1M9 17v1"/>',
    depart: '<path d="M5 12h14M13 6l6 6-6 6"/>'
  };

  function icone(nom, classe = '') {
    const tmp = document.createElement('div');
    tmp.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONES[nom] || '') + '</svg>';
    const svg = tmp.firstElementChild;
    if (classe) svg.setAttribute('class', classe);
    return svg;
  }

  function h(tag, attrs = {}, ...children) {
    const noeud = document.createElement(tag);
    Object.entries(attrs || {}).forEach(([cle, valeur]) => {
      if (valeur === null || valeur === undefined || valeur === false) return;
      if (cle === 'class') noeud.className = valeur;
      else if (cle === 'text') noeud.textContent = valeur;
      else if (cle === 'html') noeud.innerHTML = valeur; // uniquement des chaînes statiques
      else if (cle.startsWith('on') && typeof valeur === 'function') noeud.addEventListener(cle.slice(2).toLowerCase(), valeur);
      else if (valeur === true) noeud.setAttribute(cle, '');
      else noeud.setAttribute(cle, String(valeur));
    });
    children.flat().forEach((enfant) => {
      if (enfant === null || enfant === undefined || enfant === false) return;
      noeud.append(enfant instanceof Node ? enfant : document.createTextNode(String(enfant)));
    });
    return noeud;
  }

  function titre(texte, classe = '') { return h('span', { class: classe, text: texte }); }

  /* ---------- État ---------- */

  const etat = {
    moi: null,
    demandes: [],
    entreprises: [],
    etudiants: [],
    soutenances: [],
    historiques: new Map(),
    vue: 'tableau',
    filtre: 'toutes',
    recherche: '',
    statutsVus: new Map()
  };

  const contenu = document.getElementById('contenu');
  const titreTop = document.getElementById('titre-page');
  const navigation = document.getElementById('navigation');
  const rechercheInput = document.getElementById('recherche');

  const estPersonnel = () => ['responsable', 'secretariat'].includes(etat.moi?.role);
  const estResponsable = () => etat.moi?.role === 'responsable';

  /* ---------- Formatage ---------- */

  const formats = {
    date: new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }),
    dateHeure: new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  };

  function formaterDate(valeur, avecHeure = false) {
    if (!valeur) return '—';
    const d = new Date(valeur.replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? String(valeur) : (avecHeure ? formats.dateHeure : formats.date).format(d);
  }

  function formaterTaille(octets) {
    const n = Number(octets || 0);
    return n >= 1024 * 1024
      ? `${(n / (1024 * 1024)).toFixed(1)} Mo`
      : `${Math.max(1, Math.round(n / 1024))} Ko`;
  }

  function nomEntreprise(id) {
    return etat.entreprises.find((e) => String(e.id) === String(id))?.nom || `Entreprise n° ${id}`;
  }

  function nomEtudiant(id) {
    const e = etat.etudiants.find((x) => String(x.id) === String(id));
    return e ? `${e.prenom} ${e.nom}` : `Étudiant n° ${id}`;
  }

  const LIBELLES = {
    BROUILLON: 'Brouillon', SOUMISE: 'En attente', VALIDEE: 'Validée', REFUSEE: 'Refusée'
  };

  function badge(statut) {
    const cle = String(statut || '').toLowerCase();
    return h('span', { class: `badge ${cle}` }, h('span', { class: 'point' }), LIBELLES[statut] || statut);
  }

  /* ---------- Notifications ---------- */

  function toast(message, type = 'succes') {
    const zone = document.getElementById('toasts');
    const ico = icone(type === 'erreur' ? 'croix' : 'coche');
    const node = h('div', { class: `toast ${type}`, role: 'status' }, ico, h('span', { text: message }));
    zone.append(node);
    setTimeout(() => node.remove(), 4500);
  }

  /* ---------- Modales ---------- */

  function fermerModal() {
    const modale = document.getElementById('modale');
    modale.hidden = true;
    modale.replaceChildren();
  }

  function ouvrirModal({ titre: titreModal, sousTitre = '', corps, boutons = [] }) {
    const modale = document.getElementById('modale');
    modale.replaceChildren();

    const fermer = h('button', { class: 'fermer', type: 'button', 'aria-label': 'Fermer', onclick: fermerModal }, icone('croix'));
    const head = h('div', { class: 'modal-head' },
      h('div', {}, h('h3', { text: titreModal }), sousTitre ? h('p', { text: sousTitre }) : null),
      h('div', { class: 'spacer' }), fermer
    );
    const body = h('div', { class: 'modal-body' }, corps);
    const foot = h('div', { class: 'modal-foot' },
      h('button', { class: 'btn btn-ghost', type: 'button', onclick: fermerModal, text: 'Annuler' }),
      ...boutons
    );
    const carte = h('div', { class: 'modal-card', role: 'dialog', 'aria-modal': 'true', 'aria-label': titreModal }, head, body, foot);
    modale.append(carte);
    modale.hidden = false;
    setTimeout(() => carte.querySelector('input, select, button')?.focus(), 40);
  }

  function champLabel(labelText, noeud) {
    return h('div', { class: 'champ' }, h('label', { text: labelText }), noeud);
  }

  /* ---------- Chargement des données ---------- */

  async function chargerDonnees() {
    const liste = estPersonnel() ? Api.get('/api/demandes') : Api.get('/api/demandes/mes');
    const [demandes, entreprises, etudiants, soutenances] = await Promise.all([
      liste, Api.get('/api/entreprises'), Api.get('/api/etudiants'), Api.get('/api/soutenances')
    ]);
    const changements = demandes.filter((d) =>
      etat.statutsVus.has(String(d.id)) && etat.statutsVus.get(String(d.id)) !== d.statut
    );
    const avant = JSON.stringify([etat.demandes, etat.soutenances]);
    etat.demandes = demandes;
    etat.entreprises = entreprises;
    etat.etudiants = etudiants;
    etat.soutenances = soutenances;
    etat.statutsVus = new Map(demandes.map((d) => [String(d.id), d.statut]));
    etat.historiques.clear();
    return { change: avant !== JSON.stringify([demandes, soutenances]), changements };
  }

  async function recharger() {
    try {
      await chargerDonnees();
      rendre();
    } catch (e) {
      toast(e.message, 'erreur');
    }
  }

  /* ---------- Actions métier ---------- */

  async function changerStatut(id, statut, motif = null) {
    try {
      await Api.patch(`/api/demandes/${id}/statut`, motif === null ? { statut } : { statut, motif });
      await recharger();
      toast(`Demande n° ${id} : ${LIBELLES[statut].toLowerCase()}`, 'ok');
      fermerModal();
    } catch (e) {
      toast(e.message, 'erreur');
    }
  }

  function nouvelleDemande() {
    const select = h('select', { id: 'nouvelle-entreprise' },
      ...etat.entreprises.map((e) => h('option', { value: e.id, text: `${e.nom} — ${e.ville || 'ville non renseignée'}` }))
    );
    const fichier = h('input', {
      type: 'file', id: 'nouvelle-piece',
      accept: '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png'
    });
    ouvrirModal({
      titre: 'Nouvelle demande de stage',
      sousTitre: 'Choisissez l’entreprise qui vous accueillera.',
      corps: h('div', {},
        champLabel('Entreprise', select),
        champLabel('Document à joindre (facultatif)', fichier),
        h('p', { class: 'cellule-sous', text: 'Photo de la convention ou document PDF : PDF, JPG ou PNG, 2 Mo maximum.' })
      ),
      boutons: [h('button', {
        class: 'btn btn-primary', type: 'button', text: 'Créer la demande',
        onclick: async () => {
          try {
            const cree = await Api.post('/api/demandes', { entrepriseId: Number(select.value) });
            let avertissement = '';
            const selection = fichier.files[0];
            if (selection) {
              const donnees = new FormData();
              donnees.append('piece', selection);
              try {
                await Api.upload(`/api/demandes/${cree.id}/piece`, donnees);
              } catch (e) {
                // La demande existe déjà : on évite de la recréer en boucle.
                avertissement = ` Document non ajouté : ${e.message}. Utilisez « Joindre un document » dans le détail.`;
              }
            }
            await recharger();
            fermerModal();
            toast(`Demande créée. Pensez à la soumettre.${avertissement}`, avertissement ? 'erreur' : 'ok');
          } catch (e) { toast(e.message, 'erreur'); }
        }
      })]
    });
  }

  function modaleDecision(id, statut) {
    const motif = h('textarea', { id: 'motif-refus', rows: '4', placeholder: 'Indiquez précisément la raison du refus…' });
    const corps = statut === 'REFUSEE'
      ? champLabel('Motif du refus (obligatoire)', motif)
      : h('p', { class: 'cellule-sous', text: 'La demande passera en « Validée ». Vous pourrez ensuite programmer sa soutenance.' });
    ouvrirModal({
      titre: statut === 'VALIDEE' ? 'Valider la demande' : 'Refuser la demande',
      sousTitre: `Demande n° ${id}`,
      corps,
      boutons: [h('button', {
        class: `btn ${statut === 'VALIDEE' ? 'btn-success' : 'btn-danger'}`,
        type: 'button',
        text: statut === 'VALIDEE' ? 'Confirmer la validation' : 'Confirmer le refus',
        onclick: () => changerStatut(id, statut, statut === 'REFUSEE' ? motif.value : null)
      })]
    });
  }

  function modaleSoutenance(id) {
    const date = h('input', { type: 'datetime-local', id: 'date-soutenance', required: true });
    const salle = h('input', { type: 'text', id: 'salle-soutenance', maxlength: '20', placeholder: 'B12' });
    ouvrirModal({
      titre: 'Programmer une soutenance',
      sousTitre: `Demande n° ${id} — doit être validée`,
      corps: h('div', {}, champLabel('Date et heure', date), champLabel('Salle', salle)),
      boutons: [h('button', {
        class: 'btn btn-primary', type: 'button', text: 'Programmer',
        onclick: async () => {
          if (!date.value) { toast('Choisissez une date.', 'erreur'); return; }
          try {
            // Le champ datetime-local fournit "AAAA-MM-JJTHH:MM" ; l'API attend "AAAA-MM-JJ HH:MM".
            await Api.patch(`/api/demandes/${id}/soutenance`, { dateHeure: date.value.replace('T', ' '), salle: salle.value.trim() || null });
            await recharger();
            fermerModal();
            toast('Soutenance programmée.', 'ok');
          } catch (e) { toast(e.message, 'erreur'); }
        }
      })]
    });
  }

  async function modaleHistorique(id) {
    try {
      let lignes = etat.historiques.get(id);
      if (!lignes) {
        lignes = await Api.get(`/api/demandes/${id}/historique`);
        etat.historiques.set(id, lignes);
      }
      const liste = h('ul', { class: 'timeline' });
      if (!lignes.length) liste.append(h('li', {}, h('strong', { text: 'Aucune décision enregistrée' }), h('span', { text: 'Le journal se remplit après la soumission.' })));
      lignes.forEach((l) => liste.append(h('li', {},
        h('strong', { text: `${LIBELLES[l.statut_avant] || l.statut_avant} → ${LIBELLES[l.statut_apres] || l.statut_apres}` }),
        h('span', { text: `${formaterDate(l.cree_le, true)}${l.motif ? ' · ' + l.motif : ''}${l.utilisateur_email ? ' · ' + l.utilisateur_email : ''}` })
      )));
      ouvrirModal({ titre: 'Historique de la demande', sousTitre: `Demande n° ${id}`, corps: liste });
    } catch (e) { toast(e.message, 'erreur'); }
  }

  function modaleDetail(demande) {
    const s = etat.soutenances.find((x) => String(x.demande_id) === String(demande.id));
    const lignes = [
      ['Entreprise', nomEntreprise(demande.entreprise_id)],
      ['Étudiant', nomEtudiant(demande.etudiant_id)],
      ['Statut', LIBELLES[demande.statut] || demande.statut],
      ['Encadreur', demande.encadreur_id ? `n° ${demande.encadreur_id}` : 'Non affecté'],
      ['Motif de refus', demande.motif_refus || '—'],
      ['Document joint', demande.piece_nom ? `${demande.piece_nom} (${formaterTaille(demande.piece_taille)})` : 'Aucun'],
      ['Soutenance', s ? `${formaterDate(s.date_heure, true)} · ${s.salle || 'salle à confirmer'}` : 'Non programmée']
    ];
    const defs = h('div', { class: 'liste-defs' });
    lignes.forEach(([cle, valeur]) => {
      defs.append(h('div', { class: 'def-ligne' },
        h('dt', { text: cle }), h('dd', { text: String(valeur) })
      ));
    });

    const proprietaire = !estPersonnel() && String(etat.moi?.etudiant_id) === String(demande.etudiant_id);
    const apercu = demande.piece_nom && String(demande.piece_type || '').startsWith('image/')
      ? h('img', {
        class: 'apercu-piece',
        src: `/api/demandes/${demande.id}/piece?apercu=1`,
        alt: `Aperçu de ${demande.piece_nom}`
      })
      : null;
    const actionsPiece = h('div', { class: 'actions-row', style: 'margin-top:14px' },
      demande.piece_nom ? h('a', {
        class: 'btn btn-ghost', href: `/api/demandes/${demande.id}/piece`,
        target: '_blank', rel: 'noopener', text: 'Ouvrir / télécharger'
      }) : null,
      proprietaire ? h('button', {
        class: 'btn btn-primary', type: 'button',
        text: demande.piece_nom ? 'Remplacer le document' : 'Joindre un document',
        onclick: () => modalePiece(demande)
      }) : null,
      proprietaire && demande.piece_nom ? h('button', {
        class: 'btn btn-danger', type: 'button', text: 'Retirer le document',
        onclick: () => retirerPiece(demande)
      }) : null
    );

    ouvrirModal({
      titre: `Demande n° ${demande.id}`,
      sousTitre: 'Détail complet',
      corps: h('div', {}, badge(demande.statut), defs, apercu, actionsPiece),
      boutons: [h('button', { class: 'btn btn-ghost', type: 'button', text: 'Voir l’historique', onclick: () => { fermerModal(); modaleHistorique(demande.id); } })]
    });
  }

  /** Envoi ou remplacement d'un document PDF/JPG/PNG (multipart + CSRF). */
  function modalePiece(demande) {
    const fichier = h('input', {
      type: 'file', id: 'document-a-jindre',
      accept: '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png'
    });
    ouvrirModal({
      titre: demande.piece_nom ? 'Remplacer le document' : 'Joindre un document',
      sousTitre: `Demande n° ${demande.id}`,
      corps: h('div', {},
        champLabel('Document', fichier),
        h('p', { class: 'cellule-sous', text: 'PDF, JPG ou PNG, 2 Mo maximum. Le fichier est stocké hors du dossier public et reste privé.' })
      ),
      boutons: [h('button', {
        class: 'btn btn-primary', type: 'button', text: 'Envoyer le document',
        onclick: async () => {
          const selection = fichier.files[0];
          if (!selection) { toast('Choisissez un fichier.', 'erreur'); return; }
          const donnees = new FormData();
          donnees.append('piece', selection);
          try {
            await Api.upload(`/api/demandes/${demande.id}/piece`, donnees);
            await recharger();
            fermerModal();
            toast('Document ajouté.', 'ok');
          } catch (e) { toast(e.message, 'erreur'); }
        }
      })]
    });
  }

  async function retirerPiece(demande) {
    if (!window.confirm(`Retirer le document « ${demande.piece_nom} » de la demande n° ${demande.id} ?`)) return;
    try {
      await Api.supprimer(`/api/demandes/${demande.id}/piece`);
      await recharger();
      toast('Document retiré.', 'ok');
    } catch (e) { toast(e.message, 'erreur'); }
  }

  /* ---------- Composants de vue ---------- */

  function stats() {
    const total = etat.demandes.length;
    const attente = etat.demandes.filter((d) => d.statut === 'SOUMISE').length;
    const validees = etat.demandes.filter((d) => d.statut === 'VALIDEE').length;
    const refusees = etat.demandes.filter((d) => d.statut === 'REFUSEE').length;
    const items = [
      ['Total des demandes', total, 'fichier', 'Sur la période en cours'],
      ['En attente', attente, 'horloge', 'À traiter par le responsable'],
      ['Validées', validees, 'coche', 'Prêtes pour la soutenance'],
      ['Soutenances', etat.soutenances.length, 'calendrier', `${refusees} demande(s) refusée(s)`]
    ];
    return h('div', { class: 'grille-stats' }, ...items.map(([label, valeur, ico, note]) =>
      h('div', { class: 'stat' },
        h('div', { class: 'stat-ico' }, icone(ico)),
        h('div', { class: 'valeur', text: String(valeur) }),
        h('div', { class: 'libelle', text: label }),
        h('div', { class: 'tendance', text: note })
      )
    ));
  }

  function actionsPour(d) {
    const boutons = [h('button', { type: 'button', text: 'Détail', onclick: () => modaleDetail(d) })];
    if (!estPersonnel() && d.statut === 'BROUILLON') {
      boutons.push(h('button', { class: 'valider', type: 'button', text: 'Soumettre', onclick: () => changerStatut(d.id, 'SOUMISE') }));
    }
    if (estResponsable() && d.statut === 'SOUMISE') {
      boutons.push(h('button', { class: 'valider', type: 'button', text: 'Valider', onclick: () => modaleDecision(d.id, 'VALIDEE') }));
      boutons.push(h('button', { class: 'refuser', type: 'button', text: 'Refuser', onclick: () => modaleDecision(d.id, 'REFUSEE') }));
    }
    if (estPersonnel() && d.statut === 'VALIDEE') {
      boutons.push(h('button', { type: 'button', text: 'Soutenance', onclick: () => modaleSoutenance(d.id) }));
    }
    boutons.push(h('button', { type: 'button', text: 'Historique', onclick: () => modaleHistorique(d.id) }));
    return h('div', { class: 'actions-row' }, ...boutons);
  }

  function demadesFiltrees() {
    const q = etat.recherche.trim().toLowerCase();
    return etat.demandes.filter((d) => {
      const okFiltre = etat.filtre === 'toutes'
        || (etat.filtre === 'attente' && d.statut === 'SOUMISE')
        || (etat.filtre === 'validees' && d.statut === 'VALIDEE')
        || (etat.filtre === 'refusees' && d.statut === 'REFUSEE');
      const recherche = !q || `${d.id} ${nomEntreprise(d.entreprise_id)} ${nomEtudiant(d.etudiant_id)} ${d.statut}`.toLowerCase().includes(q);
      return okFiltre && recherche;
    });
  }

  function filtres() {
    const options = [['toutes', 'Toutes'], ['attente', 'En attente'], ['validees', 'Validées'], ['refusees', 'Refusées']];
    return h('div', { class: 'filtres' }, ...options.map(([valeur, label]) =>
      h('button', {
        class: `filtre ${etat.filtre === valeur ? 'actif' : ''}`,
        type: 'button', text: label,
        onclick: () => { etat.filtre = valeur; rendre(); }
      })
    ));
  }

  function tableauDemandes({ compact = false } = {}) {
    const lignes = compact ? demadesFiltrees().slice(0, 6) : demadesFiltrees();
    if (!lignes.length) {
      return h('div', { class: 'vide' },
        h('div', { class: 'vide-ico' }, icone('fichier')),
        h('strong', { text: 'Aucune demande affichée' }),
        h('p', { text: 'Modifiez le filtre ou créez une nouvelle demande.' })
      );
    }
    const colonnes = estPersonnel()
      ? ['Demande', 'Étudiant', 'Entreprise', 'Statut', 'Soutenance', 'Actions']
      : ['Demande', 'Entreprise', 'Statut', 'Soutenance', 'Actions'];
    const thead = h('thead', {}, h('tr', {}, ...colonnes.map((c) => h('th', { text: c }))));
    const tbody = h('tbody');
    lignes.forEach((d) => {
      const sout = etat.soutenances.find((s) => String(s.demande_id) === String(d.id));
      const cells = [
        h('td', {},
          h('div', { class: 'cellule-forte', text: `Demande n° ${d.id}` }),
          d.cree_le ? h('div', { class: 'cellule-sous', text: formaterDate(d.cree_le) }) : null,
          d.piece_nom ? h('div', { class: 'piece-indicateur', text: `📎 ${d.piece_nom}` }) : null
        )
      ];
      if (estPersonnel()) cells.push(h('td', {}, h('div', { class: 'cellule-forte', text: nomEtudiant(d.etudiant_id) })));
      cells.push(h('td', {}, h('div', { class: 'cellule-forte', text: nomEntreprise(d.entreprise_id) })));
      cells.push(h('td', {}, badge(d.statut)));
      cells.push(h('td', {}, h('span', { class: 'cellule-sous', text: sout ? formaterDate(sout.date_heure, true) : '—' })));
      cells.push(h('td', {}, actionsPour(d)));
      tbody.append(h('tr', { 'data-demande': d.id }, ...cells));
    });
    return h('div', { class: 'table-wrap' }, h('table', {}, thead, tbody));
  }

  function cartesEtudiant() {
    const lignes = demadesFiltrees();
    if (!lignes.length) {
      return h('div', { class: 'vide' }, h('div', { class: 'vide-ico' }, icone('fichier')),
        h('strong', { text: 'Aucune demande pour le moment' }),
        h('p', { text: 'Cliquez sur « Nouvelle demande » pour commencer.' }));
    }
    const ordre = ['BROUILLON', 'SOUMISE', 'VALIDEE'];
    return h('div', { class: 'liste-demandes' }, ...lignes.map((d) => {
      const index = d.statut === 'REFUSEE' ? 1 : ordre.indexOf(d.statut);
      const terminee = d.statut === 'VALIDEE';
      const etapes = h('div', { class: 'etapes' });
      ['Dépôt', 'Soumission', 'Décision'].forEach((libelle, i) => {
        const fait = i < index || terminee;
        etapes.append(h('div', { class: `etape ${fait ? 'fait' : i === index ? 'actuel' : ''}` },
          h('span', { class: 'rond', text: fait ? '✓' : String(i + 1) }), libelle));
        if (i < 2) etapes.append(h('span', { class: 'barre' }));
      });
      const sout = etat.soutenances.find((s) => String(s.demande_id) === String(d.id));
      return h('article', { class: 'demande-carte', 'data-demande': d.id },
        h('div', { class: 'demande-top' },
          h('div', { class: 'demande-ico' }, icone('entreprise')),
          h('div', {}, h('h4', { text: nomEntreprise(d.entreprise_id) }), h('p', { text: `Demande n° ${d.id}` })),
          h('div', { class: 'spacer' }), badge(d.statut)
        ),
        d.motif_refus ? h('p', { class: 'cellule-sous', text: `Motif : ${d.motif_refus}` }) : null,
        d.piece_nom ? h('div', { class: 'piece-indicateur' }, icone('fichier'), ` Document joint : ${d.piece_nom}`) : null,
        etapes,
        sout ? h('div', { class: 'soutenance-bande' }, icone('calendrier'), `Soutenance : ${formaterDate(sout.date_heure, true)} · ${sout.salle || 'salle à confirmer'}`) : null,
        h('div', { class: 'actions-row', style: 'margin-top:14px' },
          h('button', { type: 'button', text: 'Détail', onclick: () => modaleDetail(d) }),
          !estPersonnel() && d.statut === 'BROUILLON' ? h('button', { class: 'valider', type: 'button', text: 'Soumettre', onclick: () => changerStatut(d.id, 'SOUMISE') }) : null,
          h('button', { type: 'button', text: 'Historique', onclick: () => modaleHistorique(d.id) })
        )
      );
    }));
  }

  function activite() {
    const lignes = [...etat.demandes].sort((a, b) => Number(b.id) - Number(a.id)).slice(0, 5);
    const liste = h('ul', { class: 'timeline' });
    if (!lignes.length) liste.append(h('li', {}, h('strong', { text: 'Aucune activité' })));
    lignes.forEach((d) => liste.append(h('li', {},
      h('strong', { text: `${LIBELLES[d.statut] || d.statut} — ${nomEntreprise(d.entreprise_id)}` }),
      h('span', { text: `Demande n° ${d.id} · ${estPersonnel() ? nomEtudiant(d.etudiant_id) : 'votre dossier'}` })
    )));
    return liste;
  }

  function agenda() {
    const liste = h('div');
    if (!etat.soutenances.length) {
      liste.append(h('div', { class: 'vide' }, h('div', { class: 'vide-ico' }, icone('calendrier')),
        h('strong', { text: 'Aucune soutenance' }), h('p', { text: 'Elles apparaîtront ici après programmation.' })));
      return liste;
    }
    etat.soutenances.forEach((s) => {
      const d = new Date(s.date_heure.replace(' ', 'T'));
      liste.append(h('div', { class: 'agenda-item' },
        h('div', { class: 'agenda-date' },
          h('b', { text: Number.isNaN(d.getTime()) ? '—' : String(d.getDate()).padStart(2, '0') }),
          h('span', { text: Number.isNaN(d.getTime()) ? '' : new Intl.DateTimeFormat('fr-FR', { month: 'short' }).format(d) })
        ),
        h('div', {}, h('strong', { text: `${nomEtudiant(s.etudiant_id)} · ${s.salle || 'salle à confirmer'}` }),
          h('small', { text: `${nomEntreprise(s.entreprise_id)} · ${formaterDate(s.date_heure, true)}` }))
      ));
    });
    return liste;
  }

  function vueTableau() {
    const fiche = etat.etudiants.find((e) => e.email === etat.moi?.email);
    const prenom = fiche?.prenom || etat.moi?.email?.split('@')[0].split('.')[0] || '';
    const salutation = prenom ? prenom.charAt(0).toUpperCase() + prenom.slice(1) : 'Bonjour';
    return h('div', {},
      h('div', { class: 'page-head' },
        h('div', {}, h('h2', { text: `Bonjour ${salutation} 👋` }),
          h('p', { text: `Voici l’activité de votre espace — ${new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())}` })),
        h('div', { class: 'actions' },
          !estPersonnel() ? h('button', { class: 'btn btn-primary', type: 'button', onclick: nouvelleDemande }, icone('plus'), 'Nouvelle demande') : null,
          h('button', { class: 'btn btn-ghost', type: 'button', onclick: recharger }, icone('horloge'), 'Actualiser')
        )
      ),
      stats(),
      h('div', { class: 'grille-deux' },
        h('section', { class: 'carte' },
          h('div', { class: 'carte-head' }, h('h3', { text: 'Demandes récentes' }), h('div', { class: 'spacer' }), filtres()),
          estPersonnel() ? tableauDemandes({ compact: true }) : h('div', { class: 'carte-body' }, cartesEtudiant())
        ),
        h('div', {},
          h('section', { class: 'carte', style: 'margin-bottom:18px' },
            h('div', { class: 'carte-head' }, icone('horloge'), h('h3', { text: 'Activité récente' })),
            h('div', { class: 'carte-body' }, activite())
          ),
          h('section', { class: 'carte' },
            h('div', { class: 'carte-head' }, icone('calendrier'), h('h3', { text: 'Prochaines soutenances' })),
            h('div', { class: 'carte-body' }, agenda())
          )
        )
      )
    );
  }

  function vueDemandes() {
    return h('div', {},
      h('div', { class: 'page-head' },
        h('div', {}, h('h2', { text: estPersonnel() ? 'Toutes les demandes' : 'Mes demandes' }),
          h('p', { text: 'Recherchez, filtrez et suivez chaque dossier.' })),
        h('div', { class: 'actions' },
          !estPersonnel() ? h('button', { class: 'btn btn-primary', type: 'button', onclick: nouvelleDemande }, icone('plus'), 'Nouvelle demande') : null,
          h('button', { class: 'btn btn-ghost', type: 'button', onclick: recharger }, icone('horloge'), 'Actualiser')
        )
      ),
      h('section', { class: 'carte' },
        h('div', { class: 'carte-head' }, h('h3', { text: `${demadesFiltrees().length} résultat(s)` }), h('div', { class: 'spacer' }), filtres()),
        estPersonnel() ? tableauDemandes() : h('div', { class: 'carte-body' }, cartesEtudiant())
      )
    );
  }

  function vueSoutenances() {
    return h('div', {},
      h('div', { class: 'page-head' },
        h('div', {}, h('h2', { text: 'Agenda des soutenances' }),
          h('p', { text: 'Les soutenances planifiées, triées par date.' })),
        h('div', { class: 'actions' }, h('button', { class: 'btn btn-ghost', type: 'button', onclick: recharger }, icone('horloge'), 'Actualiser'))
      ),
      h('section', { class: 'carte' },
        h('div', { class: 'carte-head' }, icone('calendrier'), h('h3', { text: `${etat.soutenances.length} soutenance(s)` })),
        h('div', { class: 'carte-body' }, agenda())
      )
    );
  }

  function vueHistorique() {
    return h('div', {},
      h('div', { class: 'page-head' },
        h('div', {}, h('h2', { text: 'Historique des décisions' }),
          h('p', { text: 'Chaque changement de statut est journalisé côté serveur.' }))
      ),
      h('section', { class: 'carte' },
        h('div', { class: 'carte-head' }, icone('horloge'), h('h3', { text: 'Dossiers' })),
        estPersonnel() ? tableauDemandes() : h('div', { class: 'carte-body' }, cartesEtudiant())
      )
    );
  }

  function vueProfil() {
    const champs = [
      ['Adresse e-mail', etat.moi?.email],
      ['Rôle', etat.moi?.role],
      ['Identifiant compte', etat.moi?.id],
      ['Fiche étudiant liée', etat.moi?.etudiant_id ?? 'Aucune']
    ];
    const liste = h('div', { class: 'liste-defs' });
    champs.forEach(([k, v]) => liste.append(h('div', { class: 'def-ligne' }, h('dt', { text: k }), h('dd', { text: String(v ?? '—') }))));
    return h('div', {},
      h('div', { class: 'page-head' }, h('div', {}, h('h2', { text: 'Mon profil' }), h('p', { text: 'Informations de la session connectée.' }))),
      h('section', { class: 'carte', style: 'max-width:620px' },
        h('div', { class: 'carte-head' }, icone('profil'), h('h3', { text: 'Session' })),
        h('div', { class: 'carte-body' }, liste,
          h('div', { class: 'soutenance-bande', style: 'margin-top:18px' }, icone('bouclier'), 'Session sécurisée · cookie HttpOnly · protection CSRF active'))
      )
    );
  }

  const VUES = {
    tableau: { titre: 'Tableau de bord', rendre: vueTableau },
    demandes: { titre: 'Demandes', rendre: vueDemandes },
    soutenances: { titre: 'Soutenances', rendre: vueSoutenances },
    historique: { titre: 'Historique', rendre: vueHistorique },
    profil: { titre: 'Mon profil', rendre: vueProfil }
  };

  function rendre() {
    const vue = VUES[etat.vue] || VUES.tableau;
    titreTop.replaceChildren(titre('Espace '), titre(etat.moi?.role || ''), h('span', { text: ` / ${vue.titre}` }));
    contenu.replaceChildren(vue.rendre());
    [...navigation.querySelectorAll('.nav-item')].forEach((b) => {
      b.classList.toggle('actif', b.dataset.vue === etat.vue);
    });
  }

  /* ---------- Coque ---------- */

  function construireNavigation() {
    const items = [
      ['tableau', 'dashboard', 'Tableau de bord'],
      ['demandes', 'fichier', 'Demandes'],
      ['soutenances', 'calendrier', 'Soutenances'],
      ['historique', 'horloge', 'Historique'],
      ['profil', 'profil', 'Mon profil']
    ];
    navigation.replaceChildren(
      h('div', { class: 'nav-label', text: 'Navigation' }),
      ...items.map(([vue, ico, libelle]) => h('button', {
        class: `nav-item ${etat.vue === vue ? 'actif' : ''}`,
        type: 'button', 'data-vue': vue,
        onclick: () => { etat.vue = vue; rendre(); }
      }, icone(ico), libelle))
    );
  }

  function construireUtilisateur() {
    const email = etat.moi?.email || '';
    const initiales = email.slice(0, 2).toUpperCase();
    const zone = document.getElementById('utilisateur');
    const roleLibelle = { etudiant: 'Étudiant', responsable: 'Responsable', secretariat: 'Secrétariat' }[etat.moi?.role] || etat.moi?.role;
    zone.replaceChildren(
      h('div', { class: 'avatar', text: initiales }),
      h('div', {}, h('strong', { text: email.split('@')[0] }), h('small', { text: roleLibelle })),
      h('button', {
        class: 'btn-deconnexion', type: 'button', 'aria-label': 'Se déconnecter', title: 'Se déconnecter',
        onclick: async () => {
          try { await Api.deconnexion(); } catch (_) { /* on quitte quand même */ }
          Api.viderJeton();
          location.href = 'index.html';
        }
      }, icone('deconnecter'), h('span', { text: 'Se déconnecter' }))
    );
    document.getElementById('role-pill').replaceChildren(h('span', { class: 'dot' }), roleLibelle);
  }

  /* ---------- Démarrage ---------- */

  async function demarrer() {
    try {
      etat.moi = await Api.moi();
      construireNavigation();
      construireUtilisateur();
      await chargerDonnees();
      rendre();
      document.body.classList.remove('chargement');
    } catch (e) {
      toast(e.message, 'erreur');
      setTimeout(() => { location.href = 'index.html?raison=session'; }, 1200);
    }
  }

  rechercheInput.addEventListener('input', (e) => {
    etat.recherche = e.target.value;
    if (['tableau', 'demandes', 'historique'].includes(etat.vue)) rendre();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') fermerModal();
  });

  document.getElementById('modale').addEventListener('click', (e) => {
    if (e.target.id === 'modale') fermerModal();
  });

  /* Rafraîchissement automatique (15 s) : jamais pendant une saisie ou une modale. */
  let lectureEnCours = false;
  setInterval(async () => {
    if (lectureEnCours || !document.getElementById('modale').hidden || document.activeElement === rechercheInput) return;
    lectureEnCours = true;
    try {
      const { change, changements } = await chargerDonnees();
      if (change) {
        rendre();
        changements.forEach((d) => {
          const ligne = contenu.querySelector(`[data-demande="${d.id}"]`);
          if (ligne) ligne.classList.add('maj-recente');
        });
        if (changements.length) toast(`${changements.length} demande(s) mise(s) à jour.`, 'ok');
      }
    } catch (_) { /* silencieux : le bouton « Actualiser » reste disponible */ }
    finally { lectureEnCours = false; }
  }, 15000);

  demarrer();
})();
