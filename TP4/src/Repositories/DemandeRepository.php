<?php
declare(strict_types=1);

/** Seule couche autorisée à écrire du SQL (TP4 : squelette, TP6 : opérations complètes). */
final class DemandeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Requête préparée : la valeur n'est jamais interprétée comme du code SQL. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM demande_stage WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }
}
