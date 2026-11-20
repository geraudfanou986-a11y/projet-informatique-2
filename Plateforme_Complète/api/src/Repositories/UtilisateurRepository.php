<?php
declare(strict_types=1);

/** Dépôt des comptes : seul endroit où le SQL utilisateur est écrit. */
final class UtilisateurRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Le LEFT JOIN donne l'identifiant etudiant quand le compte est un etudiant. */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.email, u.hash, u.role, e.id AS etudiant_id
             FROM utilisateur u
             LEFT JOIN etudiant e ON e.email = u.email
             WHERE u.email = :email'
        );
        $stmt->execute([':email' => $email]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }

    /** Informations du compte connecté, sans le hash du mot de passe. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.email, u.role, e.id AS etudiant_id
             FROM utilisateur u
             LEFT JOIN etudiant e ON e.email = u.email
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }

    /** Un email déjà présent dans le jeu d'essai (table etudiant) est aussi refusé. */
    public function existeEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM utilisateur WHERE email = :email_utilisateur
             UNION SELECT 1 FROM etudiant WHERE email = :email_etudiant LIMIT 1'
        );
        $stmt->execute([':email_utilisateur' => $email, ':email_etudiant' => $email]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Crée le compte et, pour un étudiant, sa fiche dans la table etudiant.
     * Les deux écritures sont atomiques : soit tout passe, soit rien n'est écrit.
     */
    public function creerCompte(string $email, string $hash, string $role, string $nom, string $prenom): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO utilisateur (email, hash, role) VALUES (:email, :hash, :role)'
            );
            $stmt->execute([':email' => $email, ':hash' => $hash, ':role' => $role]);
            $id = (int) $this->pdo->lastInsertId();

            if ($role === 'etudiant') {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO etudiant (nom, prenom, email) VALUES (:nom, :prenom, :email)'
                );
                $stmt->execute([':nom' => $nom, ':prenom' => $prenom, ':email' => $email]);
            }

            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
