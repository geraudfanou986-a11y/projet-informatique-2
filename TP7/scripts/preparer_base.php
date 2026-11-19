<?php
declare(strict_types=1);

/**
 * Prépare la base de test du TP7 (à lancer depuis VS Code ou en ligne de commande).
 *
 *   php TP7/scripts/preparer_base.php
 *
 * Variables d'environnement possibles :
 *   DB_PORT  (défaut 3307), DB_PASS (défaut ''), DB_NAME (défaut tp7_corrige_test)
 */
$port = getenv('DB_PORT') ?: '3307';
$pass = getenv('DB_PASS') ?: '';
$base = getenv('DB_NAME') ?: 'tp7_corrige_test';

$pdo = new PDO("mysql:host=127.0.0.1;port=$port;charset=utf8mb4", 'root', $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("DROP DATABASE IF EXISTS `$base`");
$pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$base`");

$racine = dirname(__DIR__, 2); // dossier parent de TP7
$fichiers = [
    "$racine\\TP3\\001_schema.sql",
    "$racine\\TP3\\002_seed.sql",
    "$racine\\TP6\\sql\\01_complements.sql",
];

foreach ($fichiers as $fichier) {
    $sql = file_get_contents($fichier);
    if ($sql === false) {
        throw new RuntimeException("Lecture impossible : $fichier");
    }
    $pdo->exec($sql);
    echo 'Importé : ' . basename($fichier) . PHP_EOL;
}

echo "Base `$base` prête sur le port $port." . PHP_EOL;
