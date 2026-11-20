<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Construit l'unique connexion PDO de l'application.
 * Les exceptions PDO sont activées : aucune erreur SQL n'est ignorée.
 */
function creerPdo(): PDO
{
    // Configuration unique à la racine de la plateforme (écrite par scripts/installer.php).
    $c = Config::load(dirname(__DIR__, 2) . '/.env');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $c['host'],
        $c['port'],
        $c['name'],
        $c['charset']
    );

    return new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
