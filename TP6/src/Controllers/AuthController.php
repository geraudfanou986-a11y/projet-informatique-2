<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/http.php';
require_once __DIR__ . '/../Exceptions/HttpException.php';

/** Connexion : mot de passe haché vérifié puis identifiant de session régénéré. */
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

        repondre(200, ['connecte' => true, 'role' => $_SESSION['role']]);
    }

    public function deconnexion(): never
    {
        $_SESSION = [];
        session_destroy();
        repondre(200, ['connecte' => false]);
    }
}
