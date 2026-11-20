<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

/** Test de connexion PDO : ne jamais afficher le mot de passe. */
try {
    $pdo = creerPdo();
    $resultat = $pdo->query('SELECT 1')->fetchColumn();
    fwrite(STDOUT, "Connexion PDO OK (SELECT 1 = $resultat)\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Connexion PDO ECHOUEE : ' . get_class($e) . "\n");
    exit(1);
}
