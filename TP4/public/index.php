<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Écrit une réponse JSON et s'arrête.
 * Le détail technique des erreurs reste dans les logs du serveur.
 */
function repondre(int $code, array $donnees): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Route de santé : 200 seulement si la base est joignable.
if ($methode === 'GET' && $chemin === '/api/sante') {
    try {
        $pdo = creerPdo();
        $pdo->query('SELECT 1')->fetchColumn();
        repondre(200, ['statut' => 'ok', 'base' => 'joignable']);
    } catch (Throwable $e) {
        error_log('[TP4] echec route sante : ' . get_class($e));
        repondre(500, ['erreur' => 'Base de donnees indisponible']);
    }
}

repondre(404, ['erreur' => 'Route inconnue']);
