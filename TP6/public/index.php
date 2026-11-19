<?php
declare(strict_types=1);

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
    if ($methode === 'POST' && $chemin === '/api/deconnexion') {
        $authController->deconnexion();
    }
    if ($methode === 'GET' && $chemin === '/api/demandes') {
        $controller->lister();
    }
    if ($methode === 'GET' && $chemin === '/api/demandes/mes') {
        $controller->listerMesDemandes();
    }
    if ($methode === 'POST' && $chemin === '/api/demandes') {
        $controller->creer();
    }
    if (preg_match('#^/api/demandes/(\d+)/statut$#', $chemin, $m) === 1 && $methode === 'PATCH') {
        $controller->changerStatut((int) $m[1]);
    }

    repondre(404, ['erreur' => 'Route inconnue']);
} catch (HttpException $e) {
    repondre($e->codeHttp, ['erreur' => $e->getMessage()]);
} catch (Throwable $e) {
    // Le détail technique reste dans les logs, jamais dans la réponse.
    error_log('[TP6] ' . get_class($e) . ' : ' . $e->getMessage());
    repondre(500, ['erreur' => 'Erreur serveur']);
}
