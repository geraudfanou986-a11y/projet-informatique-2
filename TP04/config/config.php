<?php
declare(strict_types=1);

/**
 * Lit le fichier .env local (non versionné) et retourne la configuration.
 * Les valeurs par défaut servent uniquement au développement local.
 */
final class Config
{
    public static function load(string $envPath): array
    {
        $values = [];

        if (is_file($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $values[trim($parts[0])] = trim($parts[1], " \t\r\n\"'");
            }
        }

        $get = static fn(string $key, string $default): string => $values[$key] ?? (getenv($key) ?: $default);

        return [
            'host'    => $get('DB_HOST', '127.0.0.1'),
            'port'    => $get('DB_PORT', '3306'),
            'name'    => $get('DB_NAME', 'stages'),
            'user'    => $get('DB_USER', 'root'),
            'pass'    => $get('DB_PASS', ''),
            'charset' => $get('DB_CHARSET', 'utf8mb4'),
        ];
    }
}
