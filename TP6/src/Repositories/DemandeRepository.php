<?php
declare(strict_types=1);

/** Seule couche qui écrit du SQL : toutes les opérations utilisent des requêtes préparées. */
final class DemandeRepository
{
    public function __construct(private PDO $pdo)
    {
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
