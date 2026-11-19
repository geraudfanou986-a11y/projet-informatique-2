<?php
declare(strict_types=1);

/** Dépôt des comptes : seul endroit où le SQL utilisateur est écrit. */
final class UtilisateurRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, hash, role FROM utilisateur WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }
}
