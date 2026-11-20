<?php
declare(strict_types=1);

// Cookie de session non lisible en JavaScript (HttpOnly) et non envoyé en
// requête croisée (SameSite) : défense en profondeur, en plus du jeton CSRF.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off'),
]);
session_start();

$racine = dirname(__DIR__);
require_once $racine . '/config/database.php';
require_once $racine . '/src/Exceptions/HttpException.php';
require_once $racine . '/src/Support/http.php';
require_once $racine . '/src/Repositories/UtilisateurRepository.php';
require_once $racine . '/src/Repositories/DemandeRepository.php';
require_once $racine . '/src/Services/AuthService.php';
require_once $racine . '/src/Services/DemandeService.php';
require_once $racine . '/src/Controllers/AuthController.php';
require_once $racine . '/src/Controllers/DemandeController.php';

header('Content-Type: application/json; charset=utf-8');

$methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    $pdo = creerPdo();
    $auth = new AuthService(new UtilisateurRepository($pdo));
    $controller = new DemandeController(new DemandeService(new DemandeRepository($pdo)), $auth);
    $authController = new AuthController($auth);

    if ($methode === 'GET' && $chemin === '/api/sante') {
        $pdo->query('SELECT 1')->fetchColumn();
        repondre(200, ['statut' => 'ok', 'base' => 'joignable']);
    }

    if ($methode === 'POST' && $chemin === '/api/connexion') {
        $authController->connexion();
    }
    if ($methode === 'POST' && $chemin === '/api/inscription') {
        $authController->inscription();
    }
    if ($methode === 'GET' && $chemin === '/api/csrf') {
        $authController->csrf();
    }
    if ($methode === 'GET' && $chemin === '/api/moi') {
        $authController->moi();
    }
    if ($methode === 'GET' && $chemin === '/api/entreprises') {
        $controller->entreprises();
    }
    if ($methode === 'GET' && $chemin === '/api/etudiants') {
        $controller->etudiants();
    }
    if ($methode === 'GET' && $chemin === '/api/soutenances') {
        $controller->soutenances();
    }
    if ($methode === 'POST' && $chemin === '/api/deconnexion') {
        exigerCsrf();
        $authController->deconnexion();
    }
    if ($methode === 'GET' && $chemin === '/api/demandes') {
        $controller->lister();
    }
    if ($methode === 'GET' && $chemin === '/api/demandes/mes') {
        $controller->listerMesDemandes();
    }
    if (preg_match('#^/api/demandes/(\d+)$#', $chemin, $m) === 1 && $methode === 'GET') {
        $controller->detail((int) $m[1]);
    }
    if (preg_match('#^/api/demandes/(\d+)/historique$#', $chemin, $m) === 1 && $methode === 'GET') {
        $controller->historique((int) $m[1]);
    }
    if ($methode === 'POST' && $chemin === '/api/demandes') {
        exigerCsrf();
        $controller->creer();
    }
    if (preg_match('#^/api/demandes/(\d+)/statut$#', $chemin, $m) === 1 && $methode === 'PATCH') {
        exigerCsrf();
        $controller->changerStatut((int) $m[1]);
    }
    if (preg_match('#^/api/demandes/(\d+)/soutenance$#', $chemin, $m) === 1 && $methode === 'PATCH') {
        exigerCsrf();
        $controller->programmerSoutenance((int) $m[1]);
    }

    repondre(404, ['erreur' => 'Route inconnue']);
} catch (HttpException $e) {
    repondre($e->codeHttp, ['erreur' => $e->getMessage()]);
} catch (Throwable $e) {
    // Le détail technique reste dans les logs, jamais dans la réponse.
    error_log('[TP06] ' . get_class($e) . ' : ' . $e->getMessage());
    repondre(500, ['erreur' => 'Erreur serveur']);
}
