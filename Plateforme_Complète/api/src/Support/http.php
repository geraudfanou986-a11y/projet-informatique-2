<?php
declare(strict_types=1);

/** Écrit une réponse JSON et s'arrête. */
function repondre(int $code, array $donnees): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Lit et valide un corps JSON. */
function lireCorps(): array
{
    $brut = file_get_contents('php://input');
    if ($brut === false || trim($brut) === '') {
        return [];
    }
    $donnees = json_decode($brut, true);
    if (!is_array($donnees)) {
        throw new HttpException(400, 'Corps JSON invalide');
    }
    return $donnees;
}

/**
 * Jeton CSRF de la session courante (créé au besoin).
 * Le jeton est renvoyé au client par GET /api/csrf, /api/moi, /api/connexion
 * et /api/inscription ; il est vérifié sur toute écriture authentifiée.
 */
function jetonCsrf(): string
{
    if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf']) || $_SESSION['csrf'] === '') {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

/**
 * Exige l'en-tête X-CSRF-Token pour les méthodes qui modifient des données.
 * Comparaison à temps constant : la valeur reçue ne doit pas fuir par le temps de réponse.
 */
function exigerCsrf(): void
{
    $recu = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($recu) || $recu === '' || !hash_equals(jetonCsrf(), $recu)) {
        throw new HttpException(403, 'Jeton de sécurité absent ou invalide');
    }
}
