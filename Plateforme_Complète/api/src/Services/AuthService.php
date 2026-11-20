<?php
declare(strict_types=1);

/** Authentification par mot de passe haché et session régénérée. */
final class AuthService
{
    private const ROLES_INSCRIPTION = ['etudiant', 'responsable'];

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
        $_SESSION['etudiant_id'] = $utilisateur['etudiant_id'] === null
            ? null
            : (int) $utilisateur['etudiant_id'];

        return true;
    }

    /** Inscription publique : validations serveur, mot de passe haché, connexion automatique. */
    public function inscrire(array $donnees): array
    {
        $nom = trim((string) ($donnees['nom'] ?? ''));
        $prenom = trim((string) ($donnees['prenom'] ?? ''));
        $email = mb_strtolower(trim((string) ($donnees['email'] ?? '')));
        $motDePasse = (string) ($donnees['motDePasse'] ?? '');
        $role = (string) ($donnees['role'] ?? 'etudiant');

        if ($nom === '' || mb_strlen($nom) > 60 || $prenom === '' || mb_strlen($prenom) > 60) {
            throw new HttpException(400, 'Nom et prénom obligatoires (60 caractères maximum)');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 100) {
            throw new HttpException(400, 'Adresse email invalide');
        }
        if (mb_strlen($motDePasse) < 8) {
            throw new HttpException(400, 'Le mot de passe doit contenir au moins 8 caractères');
        }
        if (!in_array($role, self::ROLES_INSCRIPTION, true)) {
            throw new HttpException(400, 'Rôle invalide');
        }
        if ($this->utilisateurs->existeEmail($email)) {
            throw new HttpException(409, 'Un compte existe déjà avec cette adresse email');
        }

        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
        $this->utilisateurs->creerCompte($email, $hash, $role, $nom, $prenom);

        if (!$this->connecter($email, $motDePasse)) {
            throw new HttpException(500, 'Erreur serveur');
        }

        return $this->profil();
    }

    public function utilisateur(): array
    {
        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            throw new HttpException(401, 'Non authentifie');
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'role' => (string) $_SESSION['role'],
            'etudiant_id' => isset($_SESSION['etudiant_id']) ? (int) $_SESSION['etudiant_id'] : null,
        ];
    }

    public function exigeRole(array $roles): array
    {
        $utilisateur = $this->utilisateur();
        if (!in_array($utilisateur['role'], $roles, true)) {
            throw new HttpException(403, 'Acces interdit');
        }

        return $utilisateur;
    }

    /** Informations affichées dans l'en-tête des pages connectées. */
    public function profil(): array
    {
        $utilisateur = $this->utilisateur();
        $compte = $this->utilisateurs->findById($utilisateur['id']);

        return [
            'id' => $utilisateur['id'],
            'role' => $utilisateur['role'],
            'etudiant_id' => $utilisateur['etudiant_id'],
            'email' => $compte['email'] ?? '',
        ];
    }
}
