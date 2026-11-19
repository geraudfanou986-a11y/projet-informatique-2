<?php
declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/HttpException.php';

/**
 * Couche métier : transitions, contrôles et transaction.
 * Aucune requête SQL, aucun HTTP : seulement le dépôt reçu en dépendance.
 */
final class DemandeService
{
    /** Table des transitions autorisées (VALIDEE -> REFUSEE est interdite). */
    private const TRANSITIONS = [
        'BROUILLON' => ['SOUMISE'],
        'SOUMISE'   => ['VALIDEE', 'REFUSEE'],
        'REFUSEE'   => ['BROUILLON'],
        'VALIDEE'   => [],
    ];

    public function __construct(private DemandeRepository $repository)
    {
    }

    public function listerTout(): array
    {
        return $this->repository->findAll();
    }

    public function listerPourEtudiant(int $etudiantId): array
    {
        return $this->repository->findByStudent($etudiantId);
    }

    public function creer(int $etudiantId, mixed $entrepriseId): array
    {
        if (!is_int($entrepriseId) && !ctype_digit((string) $entrepriseId)) {
            throw new HttpException(400, 'Entreprise invalide');
        }
        $entrepriseId = (int) $entrepriseId;
        if ($entrepriseId <= 0) {
            throw new HttpException(400, 'Entreprise invalide');
        }

        $id = $this->repository->create($etudiantId, $entrepriseId);

        return ['id' => $id, 'statut' => 'BROUILLON'];
    }

    /**
     * Validation en transaction : UPDATE du statut puis INSERT de l'historique.
     * Si l'INSERT échoue, le rollback annule aussi le UPDATE (tout ou rien).
     */
    public function changerStatut(int $id, mixed $statut, mixed $motif, ?int $utilisateurId): array
    {
        if (!is_string($statut) || !isset(self::TRANSITIONS[$statut])) {
            throw new HttpException(400, 'Statut invalide');
        }
        $motif = is_string($motif) && trim($motif) !== '' ? trim($motif) : null;

        if ($statut === 'REFUSEE' && $motif === null) {
            throw new HttpException(400, 'Motif obligatoire pour un refus');
        }

        return $this->repository->transaction(function (DemandeRepository $repo) use ($id, $statut, $motif, $utilisateurId): array {
            $demande = $repo->findById($id);
            if ($demande === null) {
                throw new HttpException(404, 'Demande introuvable');
            }

            $avant = $demande['statut'];
            if (!in_array($statut, self::TRANSITIONS[$avant] ?? [], true)) {
                throw new HttpException(409, 'Transition interdite');
            }

            $repo->updateStatus($id, $statut, $motif);
            $repo->historiser($id, $avant, $statut, $motif, $utilisateurId);

            return ['id' => $id, 'statut' => $statut];
        });
    }

    /** Utilisé par les tests unitaires sans base de données. */
    public static function transitionEstAutorisee(string $avant, string $apres): bool
    {
        return in_array($apres, self::TRANSITIONS[$avant] ?? [], true);
    }
}
