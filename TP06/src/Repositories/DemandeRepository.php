<?php
declare(strict_types=1);

/** Seule couche qui écrit du SQL : toutes les opérations utilisent des requêtes préparées. */
final class DemandeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Liste des entreprises proposées dans le formulaire de dépôt. */
    public function listerEntreprises(): array
    {
        return $this->pdo
            ->query('SELECT id, nom, ville FROM entreprise ORDER BY nom')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function entrepriseExiste(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM entreprise WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM demande_stage WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }

    public function findByStudent(int $etudiantId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM demande_stage WHERE etudiant_id = :id ORDER BY id DESC');
        $stmt->execute([':id' => $etudiantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(): array
    {
        return $this->pdo->query('SELECT * FROM demande_stage ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Étudiants de l'année : sert à afficher des noms au lieu de simples identifiants. */
    public function listerEtudiants(): array
    {
        return $this->pdo
            ->query('SELECT id, nom, prenom, email FROM etudiant ORDER BY nom, prenom')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Soutenances avec les libellés utiles à l'agenda (requête préparée, aucun SQL construit). */
    public function listerSoutenances(): array
    {
        return $this->pdo
            ->query(
                'SELECT s.id, s.demande_id, s.date_heure, s.salle, d.statut,
                        d.etudiant_id, d.entreprise_id,
                        e.nom AS etudiant_nom, e.prenom AS etudiant_prenom,
                        en.nom AS entreprise_nom
                 FROM soutenance s
                 JOIN demande_stage d ON d.id = s.demande_id
                 LEFT JOIN etudiant e ON e.id = d.etudiant_id
                 LEFT JOIN entreprise en ON en.id = d.entreprise_id
                 ORDER BY s.date_heure ASC'
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Journal des décisions d'une demande, avec l'auteur quand il est connu. */
    public function historiquePour(int $demandeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.id, h.statut_avant, h.statut_apres, h.motif, h.cree_le, u.email AS utilisateur_email
             FROM historique h
             LEFT JOIN utilisateur u ON u.id = h.utilisateur_id
             WHERE h.demande_id = :id
             ORDER BY h.id DESC'
        );
        $stmt->execute([':id' => $demandeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $etudiantId, int $entrepriseId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO demande_stage (etudiant_id, entreprise_id, statut) VALUES (:e, :ent, :s)'
        );
        $stmt->execute([':e' => $etudiantId, ':ent' => $entrepriseId, ':s' => 'BROUILLON']);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $statut, ?string $motif): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE demande_stage SET statut = :s, motif_refus = :m WHERE id = :id'
        );
        $stmt->execute([':s' => $statut, ':m' => $motif, ':id' => $id]);
    }

    public function historiser(int $demandeId, string $avant, string $apres, ?string $motif, ?int $utilisateurId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO historique (demande_id, statut_avant, statut_apres, motif, utilisateur_id)
             VALUES (:d, :av, :ap, :m, :u)'
        );
        $stmt->execute([
            ':d' => $demandeId, ':av' => $avant, ':ap' => $apres,
            ':m' => $motif, ':u' => $utilisateurId,
        ]);
    }

    /**
     * Crée ou met à jour la soutenance d'une demande (une seule par demande : contrainte UNIQUE).
     * Requête préparée, valeurs liées : aucune concaténation SQL.
     */
    public function programmerSoutenance(int $demandeId, string $dateHeure, ?string $salle): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO soutenance (demande_id, date_heure, salle)
             VALUES (:d, :dh, :s)
             ON DUPLICATE KEY UPDATE date_heure = VALUES(date_heure), salle = VALUES(salle)'
        );
        $stmt->execute([':d' => $demandeId, ':dh' => $dateHeure, ':s' => $salle]);

        $stmt = $this->pdo->prepare('SELECT id, demande_id, date_heure, salle FROM soutenance WHERE demande_id = :d');
        $stmt->execute([':d' => $demandeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Tout ou rien : si une écriture échoue, la transaction est annulée.
     * Le dépôt garde la PDO : le service ne connaît pas le SQL.
     */
    public function transaction(callable $operations): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultat = $operations($this);
            $this->pdo->commit();
            return $resultat;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
