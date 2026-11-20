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

    public function entreprises(): array
    {
        return $this->repository->listerEntreprises();
    }

    public function listerPourEtudiant(int $etudiantId): array
    {
        return $this->repository->findByStudent($etudiantId);
    }

    public function etudiants(): array
    {
        return $this->repository->listerEtudiants();
    }

    /**
     * Agenda des soutenances. Un étudiant ne voit que les siennes ; le personnel voit tout.
     */
    public function soutenances(array $utilisateur): array
    {
        $toutes = $this->repository->listerSoutenances();
        if (in_array((string) ($utilisateur['role'] ?? ''), ['responsable', 'secretariat'], true)) {
            return $toutes;
        }
        $etudiantId = (int) ($utilisateur['etudiant_id'] ?? 0);

        return array_values(array_filter(
            $toutes,
            static fn(array $s): bool => (int) $s['etudiant_id'] === $etudiantId
        ));
    }

    /** Le contrôle d'accès est réutilisé : même règle que le détail d'une demande. */
    public function historique(int $id, array $utilisateur): array
    {
        $this->detail($id, $utilisateur);

        return $this->repository->historiquePour($id);
    }

    /**
     * Détail d'une demande, avec contrôle d'accès côté serveur.
     * Un étudiant ne lit que ses propres demandes (403 sinon) ; le personnel lit tout.
     */
    public function detail(int $id, array $utilisateur): array
    {
        $demande = $this->repository->findById($id);
        if ($demande === null) {
            throw new HttpException(404, 'Demande introuvable');
        }

        $role = (string) ($utilisateur['role'] ?? '');
        $autorise = in_array($role, ['responsable', 'secretariat'], true)
            || ($role === 'etudiant' && (int) $demande['etudiant_id'] === (int) ($utilisateur['etudiant_id'] ?? 0));

        if (!$autorise) {
            throw new HttpException(403, 'Cette demande ne vous est pas accessible');
        }

        return $demande;
    }

    public function creer(int $etudiantId, mixed $entrepriseId): array
    {
        if (!is_int($entrepriseId) && !ctype_digit((string) $entrepriseId)) {
            throw new HttpException(400, 'Entreprise invalide');
        }
        $entrepriseId = (int) $entrepriseId;
        if ($entrepriseId <= 0 || !$this->repository->entrepriseExiste($entrepriseId)) {
            throw new HttpException(400, 'Entreprise introuvable');
        }

        $id = $this->repository->create($etudiantId, $entrepriseId);

        return ['id' => $id, 'statut' => 'BROUILLON'];
    }

    /**
     * Validation en transaction : UPDATE du statut puis INSERT de l'historique.
     * Si l'INSERT échoue, le rollback annule aussi le UPDATE (tout ou rien).
     */
    public function changerStatut(
        int $id,
        mixed $statut,
        mixed $motif,
        ?int $utilisateurId,
        ?array $utilisateur = null
    ): array {
        // Un étudiant ne peut que SOUMETTRE son propre brouillon : les décisions
        // (VALIDEE/REFUSEE) restent réservées au responsable.
        $role = (string) ($utilisateur['role'] ?? '');
        if ($utilisateur !== null && !in_array($role, ['etudiant', 'responsable'], true)) {
            throw new HttpException(403, 'Accès interdit');
        }
        if ($utilisateur !== null && $role === 'etudiant') {
            if ($statut !== 'SOUMISE') {
                throw new HttpException(403, 'Un étudiant ne peut que soumettre sa propre demande');
            }
            $demande = $this->detail($id, $utilisateur); // 403 si ce n'est pas la sienne
            if (($demande['statut'] ?? '') !== 'BROUILLON') {
                throw new HttpException(409, 'Transition interdite');
            }
        }

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

    /**
     * Programme la soutenance d'une demande validée.
     * La date est validée strictement : format Y-m-d H:i[:s] ET date réelle du calendrier.
     * Une date impossible comme le 2026-02-30 est refusée (400) avant toute écriture.
     */
    public function programmerSoutenance(int $id, mixed $dateHeure, mixed $salle, array $utilisateur): array
    {
        if (!in_array((string) ($utilisateur['role'] ?? ''), ['responsable', 'secretariat'], true)) {
            throw new HttpException(403, 'Acces interdit');
        }

        $dateHeure = is_string($dateHeure) ? trim($dateHeure) : '';
        $format = null;
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $essai) {
            $objet = DateTime::createFromFormat($essai, $dateHeure);
            // Le reformatage détecte les débordements : 2026-02-30 devient 2026-03-02.
            if ($objet !== false && $objet->format($essai) === $dateHeure) {
                $format = $essai;
                break;
            }
        }
        if ($format === null) {
            throw new HttpException(400, 'Date de soutenance invalide');
        }

        $salle = is_string($salle) && trim($salle) !== '' ? trim($salle) : null;
        if ($salle !== null && mb_strlen($salle) > 20) {
            throw new HttpException(400, 'Salle trop longue (20 caractères maximum)');
        }

        $demande = $this->repository->findById($id);
        if ($demande === null) {
            throw new HttpException(404, 'Demande introuvable');
        }
        if ($demande['statut'] !== 'VALIDEE') {
            throw new HttpException(409, 'Seule une demande validée peut recevoir une soutenance');
        }

        $normalisee = DateTime::createFromFormat($format, $dateHeure)->format('Y-m-d H:i:s');

        return $this->repository->programmerSoutenance($id, $normalisee, $salle);
    }
}
