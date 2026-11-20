<?php
declare(strict_types=1);

/**
 * Vérification post-installation de la Plateforme Complète.
 * Utilisable depuis le navigateur (/scripts/verifier.php) ou en CLI (php scripts/verifier.php).
 * La connexion est faite avec le COMPTE APPLICATIF lu dans ../.env, jamais avec root.
 */

/**
 * Tables attendues et effectifs du jeu d'essai fictif.
 * null = la table doit exister mais son contenu est libre (documents ajoutés
 * par les étudiants pendant la démonstration).
 */
const ATTENDUS = [
    'etudiant'      => 3,
    'entreprise'    => 2,
    'encadreur'     => 2,
    'demande_stage' => 4,
    'soutenance'    => 1,
    'utilisateur'   => 3,
    'historique'    => 0,
    'piece_jointe'  => null,
];

/** Échappe une valeur pour l'affichage HTML. */
function h(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Lit le .env créé par l'installateur (même format que Config::load du TP6). */
function lireConfiguration(): array
{
    $chemin = dirname(__DIR__) . '/.env';
    if (!is_file($chemin)) {
        throw new RuntimeException('Fichier .env absent : lancez d’abord installer.php.');
    }

    $valeurs = [];
    $lignes = file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lignes === false) {
        throw new RuntimeException('Lecture du fichier .env impossible.');
    }
    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);
        if ($ligne === '' || str_starts_with($ligne, '#')) {
            continue;
        }
        $parties = explode('=', $ligne, 2);
        if (count($parties) === 2) {
            $valeurs[trim($parties[0])] = trim($parties[1], " \t\r\n\"'");
        }
    }

    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'] as $cle) {
        if (!isset($valeurs[$cle])) {
            throw new RuntimeException('Clé ' . $cle . ' absente du fichier .env.');
        }
    }

    return $valeurs;
}

/** Connexion avec le compte applicatif uniquement. */
function connecterApplicatif(array $configuration): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $configuration['DB_HOST'],
        $configuration['DB_PORT'],
        $configuration['DB_NAME'],
        $configuration['DB_CHARSET']
    );

    return new PDO($dsn, $configuration['DB_USER'], $configuration['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/** Vérifie la présence et le contenu de chaque table. */
function verifierTables(PDO $pdo): array
{
    $resultats = [];

    foreach (ATTENDUS as $table => $attendu) {
        $presence = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $presence->execute([$table]);
        if ((int) $presence->fetchColumn() === 0) {
            $resultats[] = ['table' => $table, 'attendu' => $attendu, 'obtenu' => null, 'ok' => false];
            continue;
        }

        // $table vient de la constante ATTENDUS : aucun nom issu de l'utilisateur.
        $obtenu = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        $resultats[] = [
            'table'   => $table,
            'attendu' => $attendu,
            'obtenu'  => $obtenu,
            'ok'      => $attendu === null || $obtenu === $attendu,
        ];
    }

    return $resultats;
}

/** Affichage navigateur. */
function afficherHtml(array $resultats, ?string $erreur): void
{
    ?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vérification — Plateforme Complète</title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #f5f6f8; color: #1d2433; }
    main { max-width: 760px; margin: 32px auto; padding: 24px 28px; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px #0001; }
    h1 { margin-top: 0; font-size: 1.7rem; }
    table { border-collapse: collapse; width: 100%; margin-top: 18px; }
    th, td { border: 1px solid #d5dae2; padding: 8px 10px; text-align: left; }
    th { background: #eef1f6; }
    .bloc { padding: 12px 16px; border-radius: 6px; margin: 16px 0; }
    .succes { background: #e7f6ec; border: 1px solid #9bd3ac; }
    .erreur { background: #fdecec; border: 1px solid #e3a1a1; }
    .ok { color: #19733a; font-weight: 700; }
    .manquant { color: #9c2b2b; font-weight: 700; }
    a { color: #1b5fa8; }
    code { background: #eef1f6; padding: 1px 5px; border-radius: 4px; }
  </style>
</head>
<body>
<main>
<h1>Vérification de l’installation</h1>
<?php if ($erreur !== null): ?>
  <div class="bloc erreur"><strong>ÉCHEC :</strong> <?= h($erreur) ?></div>
  <p><a href="/scripts/installer.php">Relancer l’installateur</a></p>
<?php else:
    $toutOk = true;
    foreach ($resultats as $ligne) {
        $toutOk = $toutOk && $ligne['ok'];
    } ?>
  <div class="bloc <?= $toutOk ? 'succes' : 'erreur' ?>">
    <strong><?= $toutOk ? 'OK — installation conforme.' : 'ÉCHEC — installation incomplète.' ?></strong>
    Connexion établie avec le compte applicatif du fichier <code>.env</code>.
  </div>
  <table>
    <thead><tr><th>Table</th><th>Attendu</th><th>Obtenu</th><th>Résultat</th></tr></thead>
    <tbody>
<?php foreach ($resultats as $ligne): ?>
      <tr>
        <td><code><?= h($ligne['table']) ?></code></td>
        <td><?= $ligne['attendu'] === null ? 'libre' : $ligne['attendu'] ?></td>
        <td><?= $ligne['obtenu'] === null ? 'table absente' : $ligne['obtenu'] ?></td>
        <td class="<?= $ligne['ok'] ? 'ok' : 'manquant' ?>"><?= $ligne['ok'] ? 'OK' : 'ÉCHEC' ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  <p><a href="/">Ouvrir l’application</a> · <a href="/scripts/installer.php">Relancer l’installateur</a></p>
<?php endif; ?>
</main>
</body>
</html>
<?php
}

/** Affichage CLI (texte brut). */
function afficherCli(array $resultats, ?string $erreur): void
{
    if ($erreur !== null) {
        echo '[ECHEC] ' . $erreur . PHP_EOL;
        echo 'Relancez : php scripts/installer.php (depuis le navigateur).' . PHP_EOL;

        return;
    }

    $toutOk = true;
    printf("%-16s %10s %10s %s%s", "TABLE", "ATTENDU", "OBTENU", "RESULTAT", PHP_EOL);
    foreach ($resultats as $ligne) {
        $toutOk = $toutOk && $ligne['ok'];
        printf(
            "%-16s %10s %10s %s%s",
            $ligne['table'],
            $ligne['attendu'] === null ? 'libre' : (string) $ligne['attendu'],
            $ligne['obtenu'] === null ? '-' : (string) $ligne['obtenu'],
            $ligne['ok'] ? 'OK' : 'ECHEC',
            PHP_EOL
        );
    }
    echo PHP_EOL . ($toutOk ? 'OK - installation conforme.' : 'ECHEC - installation incomplete.') . PHP_EOL;
    echo 'Application : http://127.0.0.1:8000/' . PHP_EOL;
}

$resultats = [];
$erreur = null;
$configuration = null;

try {
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('Extension pdo_mysql absente.');
    }
    $configuration = lireConfiguration();
    $resultats = verifierTables(connecterApplicatif($configuration));
} catch (Throwable $e) {
    // Le mot de passe applicatif n'est jamais inclus dans le message affiché.
    $motDePasse = $configuration['DB_PASS'] ?? '';
    $erreur = $motDePasse === '' ? $e->getMessage() : str_replace($motDePasse, '***', $e->getMessage());
}

if (PHP_SAPI === 'cli') {
    afficherCli($resultats, $erreur);
} else {
    header('Content-Type: text/html; charset=utf-8');
    afficherHtml($resultats, $erreur);
}
