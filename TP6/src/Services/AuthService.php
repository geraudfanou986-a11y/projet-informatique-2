<?php
declare(strict_types=1);

/** Authentification par mot de passe haché et session régénérée. */
final class AuthService
{
    public function __construct(private UtilisateurRepository $utilisateurs)
    {
    }

    public function connecter(string $email, string $motDePasse): bool
    {
        $utilisateur = $this->utilisateurs->findByEmail(mb_strtolower(trim($email)));

        // Même message pour compte inconnu et mot de passe faux : pas de fuite d'information.
        if ($utilisateur === null || !password_verify($motDePasse, $utilisateur['hash'])) {
            return false;
        }

        session_regenerate_id(true); // empêche la fixation de session
        $_SESSION['user_id'] = (int) $utilisateur['id'];
        $_SESSION['role'] = $utilisateur['role'];

        return true;
    }

    public function utilisateur(): array
    {
        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            throw new HttpException(401, 'Non authentifie');
        }

        return ['id' => (int) $_SESSION['user_id'], 'role' => $_SESSION['role']];
    }

    public function exigeRole(array $roles): array
    {
        $utilisateur = $this->utilisateur();
        if (!in_array($utilisateur['role'], $roles, true)) {
            throw new HttpException(403, 'Acces interdit');
        }

        return $utilisateur;
    }
}
