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
