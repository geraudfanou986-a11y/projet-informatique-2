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

    public function lister(): never
    {
        $this->auth->exigeRole(['responsable', 'secretariat']);
        repondre(200, $this->service->listerTout());
    }

    public function listerMesDemandes(): never
    {
        $u = $this->auth->exigeRole(['etudiant']);
        repondre(200, $this->service->listerPourEtudiant($u['id']));
    }

    public function creer(): never
    {
        $u = $this->auth->exigeRole(['etudiant']);
        $corps = lireCorps();
        if (!array_key_exists('entrepriseId', $corps)) {
            throw new HttpException(400, 'Entreprise invalide');
        }
        $demande = $this->service->creer($u['id'], $corps['entrepriseId']);
        repondre(201, $demande);
    }

    public function changerStatut(int $id): never
    {
        $u = $this->auth->exigeRole(['responsable']);
        $corps = lireCorps();
        $resultat = $this->service->changerStatut(
            $id,
            $corps['statut'] ?? null,
            $corps['motif'] ?? null,
            $u['id']
        );
        repondre(200, $resultat);
    }
}
