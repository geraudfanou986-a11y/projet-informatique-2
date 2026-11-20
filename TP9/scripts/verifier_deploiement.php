<?php
declare(strict_types=1);

/**
 * TP9 — vérification reproductible du déploiement.
 *
 *   php TP9/scripts/verifier_deploiement.php
 *
 * Contrôle :
 *   1. la page de santé HTTP répond 200 ;
 *   2. la configuration TP6/.env est lue par le vrai chargeur Config ;
 *   3. le compte configuré n'est pas root et se connecte à la base ;
 *   4. ce compte ne peut pas accéder à une autre base (moindre privilège) ;
 *   5. les COUNT(*) de toutes les tables sont relevés.
 *
 * Variables : TP9_BASE_URL (défaut http://127.0.0.1:8002),
 *             TP9_AUTRE_BASE (base à tester pour le refus, défaut tp7_corrige_test).
 *
 * Le script écrit TP9/preuves/resultat_verification.txt et renvoie 0 si tout est conforme.
 */

$racine = dirname(__DIR__, 2);
require_once $racine . '/TP6/config/config.php';

$baseUrl = getenv('TP9_BASE_URL') ?: 'http://127.0.0.1:8002';
$autreBase = getenv('TP9_AUTRE_BASE') ?: 'tp7_corrige_test';
$config = Config::load($racine . '/TP6/.env');

$controles = [];
$ajouter = static function (string $nom, bool $ok, string $detail) use (&$controles): void {
    $controles[] = ['controle' => $nom, 'resultat' => $ok ? 'OK' : 'ECHEC', 'detail' => $detail];
};

// 1. Page de santé HTTP.
$contexte = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
$corps = @file_get_contents($baseUrl . '/api/sante', false, $contexte);
$codeHttp = 0;
foreach ($http_response_header ?? [] as $ligne) {
    if (preg_match('~^HTTP/\S+\s+(\d{3})~', $ligne, $m) === 1) {
        $codeHttp = (int) $m[1];
    }
}
$ajouter(
    'Page de santé',
    $codeHttp === 200 && is_string($corps) && str_contains($corps, '"statut":"ok"'),
    "HTTP $codeHttp — " . trim((string) $corps)
);

// 2. Compte dédié, pas root.
$ajouter(
    'Compte non-root',
    strtolower($config['user']) !== 'root' && $config['user'] !== '',
    "DB_USER = {$config['user']}"
);

// 3. Connexion et relevé des COUNT(*).
$comptes = [];
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['name'], $config['charset']),
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $ajouter('Connexion PDO', true, "Base {$config['name']} sur {$config['host']}:{$config['port']}");
    foreach ($pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name") as $ligne) {
        $table = $ligne['table_name'];
        $comptes[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    }
    $ajouter('Relevé des tables', count($comptes) > 0, count($comptes) . ' table(s) : ' . implode(', ', array_keys($comptes)));
} catch (Throwable $e) {
    $ajouter('Connexion PDO', false, $e->getMessage());
}

// 4. Le compte ne doit pas pouvoir accéder à une autre base.
try {
    new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $autreBase, $config['charset']),
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $ajouter('Périmètre du compte', false, "Accès inattendu à $autreBase");
} catch (Throwable $e) {
    $ajouter('Périmètre du compte', true, "Accès refusé à $autreBase — " . $e->getMessage());
}

$conforme = !array_filter($controles, static fn(array $c): bool => $c['resultat'] !== 'OK');
$sortie = ['url' => $baseUrl, 'controles' => $controles, 'counts' => $comptes, 'conforme' => $conforme];
$texte = "TP9 — vérification du déploiement\n";
$texte .= "URL : $baseUrl\n\n";
foreach ($controles as $c) {
    $texte .= sprintf("[%s] %s : %s\n", $c['resultat'], $c['controle'], $c['detail']);
}
$texte .= "\nCOUNT(*) :\n" . json_encode($comptes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
$texte .= "\nRésultat : " . ($conforme ? 'CONFORME' : 'NON CONFORME') . "\n";

file_put_contents(dirname(__DIR__) . '/preuves/resultat_verification.txt', $texte);
file_put_contents(
    dirname(__DIR__) . '/preuves/resultat_verification.json',
    json_encode($sortie, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
);
echo $texte;
exit($conforme ? 0 : 1);
