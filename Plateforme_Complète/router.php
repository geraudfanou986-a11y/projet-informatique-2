<?php
declare(strict_types=1);

/**
 * Routeur du serveur PHP intégré pour la Plateforme Complète.
 *
 * - /api/*          -> API (même origine, même cookie de session, pas de CORS)
 * - /installer.php  -> installateur web (raccourci vers scripts/installer.php)
 * - /verifier.php   -> contrôle post-installation (raccourci vers scripts/verifier.php)
 * - /, /app.html…   -> fichiers statiques du dossier public/
 *
 * Lancement (une seule commande, depuis la racine de la plateforme) :
 *   php -S 127.0.0.1:8000 -t . router.php
 */

$chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($chemin, '/api/')) {
    $api = __DIR__ . '/api/public/index.php';
    if (!is_file($api)) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erreur' => 'API introuvable'], JSON_UNESCAPED_UNICODE);
        return true;
    }
    require $api;
    return true;
}

$alias = [
    '/installer.php'         => '/scripts/installer.php',
    '/verifier.php'          => '/scripts/verifier.php',
    '/scripts/installer.php' => '/scripts/installer.php',
    '/scripts/verifier.php'  => '/scripts/verifier.php',
];

if (isset($alias[$chemin])) {
    $script = __DIR__ . $alias[$chemin];
    if (!is_file($script)) {
        http_response_code(503);
        echo 'Script introuvable.';
        return true;
    }
    require $script;
    return true;
}

/** Types MIME des fichiers statiques de l'interface. */
function typeMime(string $extension): string
{
    return match ($extension) {
        'html', 'htm' => 'text/html; charset=utf-8',
        'css'         => 'text/css; charset=utf-8',
        'js'          => 'text/javascript; charset=utf-8',
        'svg'         => 'image/svg+xml',
        'png'         => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'ico'         => 'image/x-icon',
        'json'        => 'application/json; charset=utf-8',
        default       => 'application/octet-stream',
    };
}

$relatif = ltrim($chemin, '/');
if ($relatif === '') {
    $relatif = 'index.html';
}
$candidat = __DIR__ . '/public/' . str_replace('/', DIRECTORY_SEPARATOR, $relatif);
$fichier = realpath($candidat);
$racinePublique = realpath(__DIR__ . '/public');

// realpath + préfixe : empêche toute remontée hors du dossier public/.
if ($fichier !== false && $racinePublique !== false
    && str_starts_with($fichier, $racinePublique . DIRECTORY_SEPARATOR)
    && is_file($fichier)) {
    header('Content-Type: ' . typeMime(strtolower(pathinfo($fichier, PATHINFO_EXTENSION))));
    readfile($fichier);
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Page introuvable.';
return true;
