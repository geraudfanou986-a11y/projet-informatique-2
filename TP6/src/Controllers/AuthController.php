<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/http.php';
require_once __DIR__ . '/../Exceptions/HttpException.php';

/** Connexion, inscription et profil : le contrôleur ne contient aucun SQL. */
final class AuthController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function connexion(): never
    {
        $corps = lireCorps();
        $email = $corps['email'] ?? null;
        $motDePasse = $corps['motDePasse'] ?? null;

        if (!is_string($email) || !is_string($motDePasse) || $email === '' || $motDePasse === '') {
            throw new HttpException(400, 'Identifiants invalides');
        }

        if (!$this->auth->connecter($email, $motDePasse)) {
            throw new HttpException(401, 'Identifiants invalides');
        }

        repondre(200, [
            'connecte' => true,
            'role' => $_SESSION['role'],
            'email' => $this->auth->profil()['email'],
            'csrf' => jetonCsrf(),
        ]);
    }

    /** Création d'un compte par n'importe quel visiteur. */
    public function inscription(): never
    {
        $compte = $this->auth->inscrire(lireCorps());

        repondre(201, [
            'connecte' => true,
            'role' => $compte['role'],
            'email' => $compte['email'],
            'etudiant_id' => $compte['etudiant_id'],
            'message' => 'Compte créé avec succès.',
            'csrf' => jetonCsrf(),
        ]);
    }

    /** Jeton CSRF de la session : appelé avant une écriture. */
    public function csrf(): never
    {
        repondre(200, ['csrf' => jetonCsrf()]);
    }

    /** Profil du visiteur connecté, utilisé par l'en-tête des pages. */
    public function moi(): never
    {
        repondre(200, $this->auth->profil() + ['csrf' => jetonCsrf()]);
    }

    public function deconnexion(): never
    {
        $_SESSION = [];
        session_destroy();
        repondre(200, ['connecte' => false]);
    }
}
