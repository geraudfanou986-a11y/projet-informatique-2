<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/http.php';
require_once __DIR__ . '/../Exceptions/HttpException.php';

/** Couche HTTP : lit la requête, appelle le service, écrit la réponse. Aucun SQL ici. */
final class DemandeController
{
    public function __construct(
        private DemandeService $service,
        private AuthService $auth
    ) {
    }

    /** Liste des entreprises pour le formulaire de dépôt (utilisateur authentifié). */
    public function entreprises(): never
    {
        $this->auth->utilisateur();
        repondre(200, $this->service->entreprises());
    }

    public function lister(): never
    {
        $this->auth->exigeRole(['responsable', 'secretariat']);
        repondre(200, $this->service->listerTout());
    }

    public function listerMesDemandes(): never
    {
        $u = $this->auth->exigeRole(['etudiant']);
        if ($u['etudiant_id'] === null) {
            throw new HttpException(403, 'Aucune fiche étudiant liée à ce compte');
        }
        repondre(200, $this->service->listerPourEtudiant($u['etudiant_id']));
    }

    /** Noms des étudiants pour l'affichage (utilisateur authentifié). */
    public function etudiants(): never
    {
        $this->auth->utilisateur();
        repondre(200, $this->service->etudiants());
    }

    /** Agenda des soutenances, filtré selon le rôle dans le service. */
    public function soutenances(): never
    {
        $u = $this->auth->utilisateur();
        repondre(200, $this->service->soutenances($u));
    }

    /** Journal des décisions d'une demande (contrôle d'accès dans le service). */
    public function historique(int $id): never
    {
        $u = $this->auth->utilisateur();
        repondre(200, $this->service->historique($id, $u));
    }

    /** Détail d'une demande : le service applique le contrôle d'accès par rôle. */
    public function detail(int $id): never
    {
        $u = $this->auth->utilisateur();
        repondre(200, $this->service->detail($id, $u));
    }

    /** Programmation d'une soutenance (personnel uniquement). */
    public function programmerSoutenance(int $id): never
    {
        $u = $this->auth->exigeRole(['responsable', 'secretariat']);
        $corps = lireCorps();
        $soutenance = $this->service->programmerSoutenance(
            $id,
            $corps['dateHeure'] ?? null,
            $corps['salle'] ?? null,
            $u
        );
        repondre(200, $soutenance);
    }

    public function creer(): never
    {
        $u = $this->auth->exigeRole(['etudiant']);
        if ($u['etudiant_id'] === null) {
            throw new HttpException(403, 'Aucune fiche étudiant liée à ce compte');
        }
        $corps = lireCorps();
        if (!array_key_exists('entrepriseId', $corps)) {
            throw new HttpException(400, 'Entreprise invalide');
        }
        $demande = $this->service->creer($u['etudiant_id'], $corps['entrepriseId']);
        repondre(201, $demande);
    }

    public function changerStatut(int $id): never
    {
        // Le service distingue ensuite les rôles : un étudiant peut uniquement
        // soumettre sa propre demande, le responsable décide (valider/refuser).
        $u = $this->auth->utilisateur();
        $corps = lireCorps();
        $resultat = $this->service->changerStatut(
            $id,
            $corps['statut'] ?? null,
            $corps['motif'] ?? null,
            $u['id'],
            $u
        );
        repondre(200, $resultat);
    }
}
