<?php
declare(strict_types=1);

/**
 * Installateur WEB autonome de la Plateforme Complète (PHP 8.1+, PDO MySQL).
 * Aucun composer : ouvrir http://127.0.0.1:8000/installer.php et remplir le formulaire.
 *
 * Rôle :
 *  - vérifier PHP >= 8.1 et l'extension pdo_mysql ;
 *  - créer la base, puis un compte applicatif dédié (jamais root dans l'application) ;
 *  - importer les 4 scripts SQL dans l'ordre avec PDO::exec ;
 *  - écrire le .env lu par config/config.php ;
 *  - permettre une réinstallation propre de la seule base déclarée dans le formulaire.
 *
 * Le mot de passe administrateur n'est jamais réaffiché, journalisé ou envoyé au navigateur.
 */

/** Scripts SQL à importer, dans l'ordre (TP3, TP6 puis table des documents joints). */
const FICHIERS_SQL = ['001_schema.sql', '002_seed.sql', '003_complements.sql', '004_piece_jointe.sql'];

/** Échappe une valeur pour l'affichage HTML. */
function h(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Lit config/.env.example pour préremplir le formulaire.
 * La syntaxe est la même que celle acceptée par Config::load() (TP6).
 */
function valeursParDefaut(): array
{
    $valeurs = [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'stages_final',
        'DB_USER' => 'stages_app',
        'DB_PASS' => 'StagesApp123!',
        'DB_CHARSET' => 'utf8mb4',
    ];

    $modele = dirname(__DIR__) . '/config/.env.example';
    if (is_file($modele)) {
        $lignes = file($modele, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lignes !== false) {
            foreach ($lignes as $ligne) {
                $ligne = trim($ligne);
                if ($ligne === '' || str_starts_with($ligne, '#')) {
                    continue;
                }
                $parties = explode('=', $ligne, 2);
                if (count($parties) === 2 && array_key_exists($parties[0], $valeurs)) {
                    $valeurs[$parties[0]] = trim($parties[1], " \t\r\n\"'");
                }
            }
        }
    }

    return [
        'host'       => $valeurs['DB_HOST'],
        'port'       => $valeurs['DB_PORT'],
        'name'       => $valeurs['DB_NAME'],
        'user'       => $valeurs['DB_USER'],
        'pass'       => $valeurs['DB_PASS'],
        'admin_user' => 'root',
        'admin_pass' => '', // Jamais prérempli : saisi à chaque installation.
    ];
}

/** Reprend les valeurs envoyées par le formulaire (mots de passe non « trimés »). */
function lireFormulaire(): array
{
    $post = static fn(string $cle): string => is_string($_POST[$cle] ?? null) ? $_POST[$cle] : '';

    return [
        'host'       => trim($post('host')),
        'port'       => trim($post('port')),
        'name'       => trim($post('name')),
        'user'       => trim($post('user')),
        'pass'       => $post('pass'),
        'admin_user' => trim($post('admin_user')),
        'admin_pass' => $post('admin_pass'),
    ];
}

/** Vérifie qu'un identifiant SQL (base ou utilisateur) ne contient que [A-Za-z0-9_]. */
function validerIdentifiant(string $valeur, string $champ, int $longueurMax): string
{
    if ($valeur === '' || preg_match('/\A[A-Za-z0-9_]{1,' . $longueurMax . '}\z/D', $valeur) !== 1) {
        throw new RuntimeException(
            $champ . ' : utilisez uniquement des lettres, des chiffres et le tiret bas (1 à '
            . $longueurMax . ' caractères).'
        );
    }

    return $valeur;
}

/** Valide toutes les valeurs du formulaire ; les identifiants sont ensuite sûrs à intercaler en SQL. */
function validerFormulaire(array $donnees): array
{
    $hote = $donnees['host'];
    if ($hote === '' || preg_match('/\A[A-Za-z0-9_.-]{1,255}\z/D', $hote) !== 1) {
        throw new RuntimeException('Hôte invalide : utilisez un nom d’hôte ou une adresse IPv4.');
    }

    $port = filter_var($donnees['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false) {
        throw new RuntimeException('Port invalide : indiquez un nombre entre 1 et 65535.');
    }

    foreach (['pass' => 'Mot de passe applicatif', 'admin_pass' => 'Mot de passe administrateur'] as $cle => $libelle) {
        if (preg_match('/[\r\n\0]/', $donnees[$cle]) === 1) {
            throw new RuntimeException($libelle . ' : les retours à la ligne ne sont pas autorisés.');
        }
    }

    return [
        'host'       => $hote,
        'port'       => (int) $port,
        'name'       => validerIdentifiant($donnees['name'], 'Nom de la base', 64),
        'user'       => validerIdentifiant($donnees['user'], 'Utilisateur applicatif', 32),
        'pass'       => $donnees['pass'],
        'admin_user' => validerIdentifiant($donnees['admin_user'], 'Utilisateur administrateur MySQL', 32),
        'admin_pass' => $donnees['admin_pass'],
        'charset'    => 'utf8mb4',
    ];
}

/** Retourne les prérequis sous forme de liste lisible (affichée dans le formulaire). */
function prerequis(): array
{
    return [
        [
            'ok'    => PHP_VERSION_ID >= 80100,
            'texte' => 'PHP 8.1 ou supérieur (version détectée : ' . PHP_VERSION . ')',
        ],
        [
            'ok'    => extension_loaded('pdo_mysql'),
            'texte' => 'Extension PDO MySQL active (pdo_mysql)',
        ],
    ];
}

/** Interrompt l'installation si un prérequis manque. */
function exigerPrerequis(): void
{
    foreach (prerequis() as $prerequis) {
        if (!$prerequis['ok']) {
            throw new RuntimeException('Prérequis manquant : ' . $prerequis['texte'] . '.');
        }
    }
}

/** Place un identifiant déjà validé entre backticks (aucune interpolation possible). */
function identifiantSql(string $nom): string
{
    return '`' . $nom . '`';
}

/** Connexion administrative : sert uniquement à créer la base, le compte et importer le SQL. */
function connecterAdministrateur(array $config): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], $config['port']);

    return new PDO($dsn, $config['admin_user'], $config['admin_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/** La base existe-t-elle déjà ? */
function baseExiste(PDO $pdo, string $base): bool
{
    $requete = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $requete->execute([$base]);

    return (int) $requete->fetchColumn() > 0;
}

/** Nombre de tables présentes dans une base (détection d'une installation antérieure). */
function nombreTables(PDO $pdo, string $base): int
{
    $requete = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = \'BASE TABLE\''
    );
    $requete->execute([$base]);

    return (int) $requete->fetchColumn();
}

/**
 * Prépare la base. Retourne [déjà installée, base créée/recréée par nous].
 * Si la base contient déjà des tables, rien n'est modifié sans accord explicite
 * (bouton « Réinstaller »), afin d'éviter une demi-installation.
 */
function preparerBase(PDO $admin, array $config, bool $reinstall): array
{
    $nomBase = identifiantSql($config['name']);
    $existe = baseExiste($admin, $config['name']);
    $tables = $existe ? nombreTables($admin, $config['name']) : 0;

    if ($reinstall) {
        // Suppression limitée à la base nommée dans le formulaire (validée plus haut).
        $admin->exec('DROP DATABASE IF EXISTS ' . $nomBase);
        $admin->exec(sprintf(
            'CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $nomBase
        ));
        $admin->exec('USE ' . $nomBase);

        return [false, true];
    }

    if ($tables > 0) {
        return [true, false]; // On s'arrête : l'utilisateur doit choisir « Réinstaller ».
    }

    if (!$existe) {
        $admin->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $nomBase
        ));
    }
    $admin->exec('USE ' . $nomBase);

    return [false, !$existe];
}

/** Importe les 4 fichiers SQL dans l'ordre avec PDO::exec (commentaires et INSERT inclus). */
function importerSql(PDO $admin): array
{
    $importes = [];
    $dossier = dirname(__DIR__) . '/sql';

    foreach (FICHIERS_SQL as $fichier) {
        $chemin = $dossier . '/' . $fichier;
        $sql = @file_get_contents($chemin);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Lecture impossible du fichier SQL : ' . $fichier . '.');
        }
        $admin->exec($sql); // MySQL/MariaDB acceptent plusieurs instructions par exec().
        $importes[] = $fichier;
    }

    return $importes;
}

/**
 * Crée le compte applicatif et ne lui accorde que les droits nécessaires,
 * exclusivement sur la base choisie. Les variantes d'hôte couvrent les
 * connexions locales en TCP et par socket.
 */
function creerCompteApplicatif(PDO $admin, array $config): void
{
    $utilisateur = $admin->quote($config['user']);
    $motDePasse = $admin->quote($config['pass']); // PDO::quote protège le littéral SQL.
    $base = identifiantSql($config['name']);
    $hotes = ['localhost', '127.0.0.1', '::1'];

    foreach ($hotes as $hote) {
        $compte = $utilisateur . '@' . $admin->quote($hote);
        $admin->exec('CREATE USER IF NOT EXISTS ' . $compte . ' IDENTIFIED BY ' . $motDePasse);
        $admin->exec('ALTER USER ' . $compte . ' IDENTIFIED BY ' . $motDePasse);
        $admin->exec(sprintf(
            'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON %s.* TO %s',
            $base,
            $compte
        ));
    }

    // MariaDB 10.4+ applique les GRANT immédiatement : FLUSH n'est plus nécessaire.
    // Il reste tenté pour les versions anciennes, mais un échec (droits insuffisants
    // ou table système Aria endommagée chez l'hébergeur) ne doit pas bloquer l'installation.
    try {
        $admin->exec('FLUSH PRIVILEGES');
    } catch (Throwable $ignore) {
        // Les GRANT ci-dessus sont déjà actifs : on continue sans interrompre l'installation.
    }
}

/** Formate une valeur pour le .env (format lu par Config::load). */
function formaterValeurEnv(string $valeur): string
{
    if (preg_match('/[\r\n\0"]/', $valeur) === 1) {
        throw new RuntimeException('Une valeur contient un caractère interdit dans le fichier .env.');
    }

    // Les espaces et le dièse sont placés entre guillemets : Config::load() les retire.
    return ($valeur === '' || trim($valeur) !== $valeur || str_contains($valeur, '#'))
        ? '"' . $valeur . '"'
        : $valeur;
}

/** Écrit ../.env (racine de la plateforme) avec le compte applicatif, puis protège le fichier. */
function ecrireEnv(array $config): string
{
    $chemin = dirname(__DIR__) . '/.env';
    $lignes = [
        'DB_HOST=' . formaterValeurEnv($config['host']),
        'DB_PORT=' . formaterValeurEnv((string) $config['port']),
        'DB_NAME=' . formaterValeurEnv($config['name']),
        'DB_USER=' . formaterValeurEnv($config['user']),
        'DB_PASS=' . formaterValeurEnv($config['pass']),
        'DB_CHARSET=' . formaterValeurEnv($config['charset']),
    ];

    if (file_put_contents($chemin, implode(PHP_EOL, $lignes) . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Écriture du fichier .env impossible : vérifiez les droits du dossier.');
    }
    @chmod($chemin, 0600); // Sans effet sur certains systèmes (Windows) : on ignore.

    return $chemin;
}

/**
 * Lors d'une réinstallation, les anciens documents joints n'ont plus de ligne
 * dans la base : on les supprime du dossier stockage/ pour éviter des orphelins.
 * Le fichier .gitkeep est conservé.
 */
function nettoyerStockage(): void
{
    $dossier = dirname(__DIR__) . '/stockage';
    if (!is_dir($dossier)) {
        return;
    }
    foreach (scandir($dossier) ?: [] as $entree) {
        if ($entree === '.' || $entree === '..' || $entree === '.gitkeep') {
            continue;
        }
        $chemin = $dossier . DIRECTORY_SEPARATOR . $entree;
        if (is_file($chemin)) {
            @unlink($chemin);
        }
    }
}

/** Masque le mot de passe administrateur dans tout message d'erreur éventuellement affiché. */
function messageSecurise(Throwable $exception, string $secretAdmin): string
{
    $message = $exception->getMessage();

    return $secretAdmin === '' ? $message : str_replace($secretAdmin, '***', $message);
}

/** Début de page HTML. */
function enteteHtml(string $titre): void
{
    ?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($titre) ?></title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #f5f6f8; color: #1d2433; }
    main { max-width: 760px; margin: 32px auto; padding: 24px 28px; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px #0001; }
    h1 { margin-top: 0; font-size: 1.7rem; }
    h2 { font-size: 1.15rem; margin-top: 26px; }
    label { display: block; margin-top: 12px; font-weight: 600; }
    input { box-sizing: border-box; width: 100%; max-width: 460px; padding: 8px 10px; border: 1px solid #b8c0cc; border-radius: 5px; font: inherit; }
    button { margin-top: 22px; padding: 10px 18px; border: 0; border-radius: 5px; background: #1b5fa8; color: #fff; font: inherit; cursor: pointer; }
    button.danger { background: #9c2b2b; margin-left: 8px; }
    .bloc { padding: 12px 16px; border-radius: 6px; margin: 16px 0; }
    .erreur { background: #fdecec; border: 1px solid #e3a1a1; }
    .avertissement { background: #fff6df; border: 1px solid #e8cf8a; }
    .succes { background: #e7f6ec; border: 1px solid #9bd3ac; }
    .ok { color: #19733a; font-weight: 700; }
    .manquant { color: #9c2b2b; font-weight: 700; }
    ul.prerequis { padding-left: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 12px; }
    th, td { border: 1px solid #d5dae2; padding: 8px 10px; text-align: left; }
    th { background: #eef1f6; }
    code { background: #eef1f6; padding: 1px 5px; border-radius: 4px; }
    a { color: #1b5fa8; }
    small { color: #5b6472; }
  </style>
</head>
<body>
<main>
<h1><?= h($titre) ?></h1>
<?php
}

/** Fin de page HTML. */
function piedHtml(): void
{
    ?>
</main>
</body>
</html>
<?php
}

/** Formulaire d'installation (le mot de passe administrateur n'est jamais réaffiché). */
function afficherFormulaire(array $donnees, ?string $erreur, ?string $avertissement): void
{
    enteteHtml('Installation — Plateforme Complète');
    echo '<p>Cet assistant prépare la base <code>stages_final</code>, crée un compte applicatif dédié, '
        . 'importe les données de démonstration et écrit le fichier <code>.env</code>.</p>';

    echo '<h2>Prérequis</h2><ul class="prerequis">';
    foreach (prerequis() as $prerequis) {
        $classe = $prerequis['ok'] ? 'ok' : 'manquant';
        $mot = $prerequis['ok'] ? 'OK' : 'ÉCHEC';
        echo '<li class="' . $classe . '">[' . $mot . '] ' . h($prerequis['texte']) . '</li>';
    }
    echo '</ul>';

    if ($erreur !== null) {
        echo '<div class="bloc erreur"><strong>Échec de l’installation :</strong> ' . h($erreur) . '</div>';
    }
    if ($avertissement !== null) {
        echo '<div class="bloc avertissement"><strong>Attention :</strong> ' . h($avertissement) . '</div>';
    }

    $champs = [
        ['host', 'Hôte MySQL', 'text'],
        ['port', 'Port MySQL', 'text'],
        ['name', 'Nom de la base', 'text'],
        ['user', 'Utilisateur applicatif (non root)', 'text'],
        ['pass', 'Mot de passe applicatif', 'password'],
        ['admin_user', 'Utilisateur administrateur MySQL', 'text'],
    ];
    ?>
<h2>Paramètres</h2>
<form method="post" action="">
<?php foreach ($champs as [$cle, $libelle, $type]): ?>
  <label for="<?= h($cle) ?>"><?= h($libelle) ?></label>
  <input id="<?= h($cle) ?>" name="<?= h($cle) ?>" type="<?= h($type) ?>"
         value="<?= h((string) $donnees[$cle]) ?>" autocomplete="off">
<?php endforeach; ?>
  <label for="admin_pass">Mot de passe administrateur MySQL</label>
  <input id="admin_pass" name="admin_pass" type="password" value="" autocomplete="new-password">
  <small>Pour des raisons de sécurité, ce mot de passe n'est jamais conservé ni réaffiché.
    Saisissez-le à nouveau lors de chaque installation ou réinstallation.</small>

  <div>
    <button type="submit" name="action" value="installer">Installer la plateforme</button>
<?php if ($avertissement !== null): ?>
    <button class="danger" type="submit" name="action" value="reinstall"
            onclick="return confirm('La base indiquée dans le formulaire va être supprimée puis recréée. Continuer ?');">
      Réinstaller (efface la base)
    </button>
<?php endif; ?>
  </div>
</form>
<?php
    piedHtml();
}

/** Écran de succès : accès à l'application, vérification et comptes de démonstration. */
function afficherSucces(array $config, array $importes, string $cheminEnv): void
{
    enteteHtml('Installation terminée — Plateforme Complète');
    echo '<div class="bloc succes"><strong>Installation réussie.</strong> Base <code>'
        . h($config['name']) . '</code> prête, compte applicatif <code>' . h($config['user'])
        . '</code> créé et configuration écrite dans <code>' . h($cheminEnv) . '</code>.</div>';
    echo '<p>Fichiers SQL importés dans l’ordre : ' . h(implode(', ', $importes)) . '.</p>';
    echo '<p><a href="/">Ouvrir l’application</a> · '
        . '<a href="/scripts/verifier.php">Vérifier l’installation</a> · '
        . '<a href="/scripts/installer.php">Relancer l’installateur</a></p>';

    echo '<h2>Comptes fictifs de démonstration</h2>';
    echo '<table><thead><tr><th>Rôle</th><th>Email</th><th>Mot de passe</th></tr></thead><tbody>';
    $comptes = [
        ['Étudiant', 'koffi.adjovi@univ.test', 'Etudiant123!'],
        ['Responsable', 'sylvie.hounkpe@univ.test', 'Responsable123!'],
        ['Secrétariat', 'jean.akpo@univ.test', 'Secretariat123!'],
    ];
    foreach ($comptes as [$role, $email, $motDePasse]) {
        echo '<tr><td>' . h($role) . '</td><td><code>' . h($email) . '</code></td>'
            . '<td><code>' . h($motDePasse) . '</code></td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><small>Ces comptes et ces données sont fictifs. Le compte administrateur MySQL '
        . 'n’est utilisé que par cet installateur et n’apparaît jamais dans l’application.</small></p>';
    piedHtml();
}

// ---------------------------------------------------------------------------
// Traitement de la requête
// ---------------------------------------------------------------------------
$donnees = valeursParDefaut();
$erreur = null;
$avertissement = null;
$admin = null;
$supprimerEnEchec = false;
$config = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $donnees = lireFormulaire();
    $reinstall = (($_POST['action'] ?? '') === 'reinstall');

    try {
        exigerPrerequis();
        $config = validerFormulaire($donnees);
        $admin = connecterAdministrateur($config);
        [$dejaInstalle, $supprimerEnEchec] = preparerBase($admin, $config, $reinstall);

        if ($dejaInstalle) {
            $avertissement = 'La base « ' . $config['name'] . ' » contient déjà des tables : aucune modification '
                . 'n’a été effectuée. Cliquez sur « Réinstaller » pour supprimer puis recréer uniquement cette base.';
        } else {
            $importes = importerSql($admin);
            creerCompteApplicatif($admin, $config);
            $cheminEnv = ecrireEnv($config);
            if ($reinstall) {
                nettoyerStockage();
            }
            afficherSucces($config, $importes, $cheminEnv);
            exit;
        }
    } catch (Throwable $e) {
        // En cas d'échec sur une base que nous venons de créer, on évite de laisser
        // une installation partielle : la base est supprimée et l'utilisateur est
        // renvoyé vers le formulaire pour repartir proprement.
        if ($admin !== null && $supprimerEnEchec && $config !== null) {
            try {
                $admin->exec('DROP DATABASE IF EXISTS ' . identifiantSql($config['name']));
            } catch (Throwable $ignore) {
                // Nettoyage best-effort : le message utile reste celui de l'exception d'origine.
            }
        }

        if ($e instanceof PDOException && $admin === null) {
            $erreur = 'Connexion administrateur impossible (SQLSTATE ' . $e->getCode() . '). '
                . 'Vérifiez l’hôte, le port, l’utilisateur et le mot de passe administrateur.';
        } else {
            $erreur = messageSecurise($e, $donnees['admin_pass']);
        }
    }
}

afficherFormulaire($donnees, $erreur, $avertissement);
