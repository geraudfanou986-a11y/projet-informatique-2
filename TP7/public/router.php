<?php
declare(strict_types=1);

/**
 * Routeur du serveur de développement du TP7.
 *
 * - /api/* est délégué à l'API réelle du TP6 (même origine, même cookie de session) ;
 * - les autres chemins sont servis comme fichiers statiques par PHP.
 *
 * Démarrage :
 *   php -S 127.0.0.1:8001 -t public public/router.php
 */
$chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($chemin, '/api/')) {
    $api = dirname(__DIR__, 2) . '/TP6/public/index.php';

    if (!is_file($api)) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erreur' => 'API du TP6 introuvable'], JSON_UNESCAPED_UNICODE);
        return true;
    }

    require $api;
    return true;
}

// false = le serveur intégré sert le fichier statique demandé.
return false;
