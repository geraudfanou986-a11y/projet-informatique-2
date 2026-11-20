<?php
declare(strict_types=1);

/**
 * TP08 — Plan de tests et audit de sécurité.
 *
 * Exécute la matrice de tests contre l'application réellement lancée
 * (interface TP07 + API TP06 sur le même port) et écrit les preuves.
 *
 * Usage :
 *   php TP08/scripts/tests_tp8.php
 *
 * Variables d'environnement :
 *   TP8_BASE_URL (défaut http://127.0.0.1:8001)
 *   TP8_DB_PORT  (défaut 3307)   TP8_DB_NAME (défaut tp7_corrige_test)
 *   TP8_DB_USER  (défaut root)   TP8_DB_PASS (défaut vide)
 *
 * Le script crée ses propres comptes et demandes de test, puis les supprime
 * à la fin : la base de démonstration n'est pas polluée.
 */

$base = rtrim(getenv('TP8_BASE_URL') ?: 'http://127.0.0.1:8001', '/');
$dbPort = getenv('TP8_DB_PORT') ?: '3307';
$dbName = getenv('TP8_DB_NAME') ?: 'tp7_corrige_test';
$dbUser = getenv('TP8_DB_USER') ?: 'root';
$dbPass = getenv('TP8_DB_PASS') !== false ? (string) getenv('TP8_DB_PASS') : '';

$dossierPreuves = dirname(__DIR__) . '/preuves';
if (!is_dir($dossierPreuves)) {
    mkdir($dossierPreuves, 0777, true);
}
$dossierCookies = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tp8_cookies_' . getmypid();
if (!is_dir($dossierCookies)) {
    mkdir($dossierCookies, 0777, true);
}

/** @var list<array<string,string>> $resultats */
$resultats = [];
$numero = 0;

function verifier(string $cas, string $exigence, string $donnees, string $attendu, string $observe, bool $conforme, string $preuve = ''): void
{
    global $resultats, $numero;
    $numero++;
    $resultats[] = [
        'numero' => (string) $numero,
        'cas' => $cas,
        'exigence' => $exigence,
        'donnees' => $donnees,
        'attendu' => $attendu,
        'observe' => $observe,
        'conforme' => $conforme ? 'OUI' : 'NON',
        'preuve' => $preuve,
    ];
    printf("[%s] T-%02d %s\n", $conforme ? 'OK ' : 'NON', $numero, $cas);
}

/**
 * Requête HTTP réelle avec gestion des cookies (une session par pot de cookies).
 *
 * @return array{statut:int, corps:array<string,mixed>, brut:string, erreur:string}
 */
function requete(string $methode, string $chemin, ?array $corps, string $pot, ?string $csrf = null): array
{
    global $base;
    $entetes = ['Accept: application/json'];
    if ($corps !== null) {
        $entetes[] = 'Content-Type: application/json';
    }
    if ($csrf !== null && $csrf !== '') {
        $entetes[] = 'X-CSRF-Token: ' . $csrf;
    }

    $ch = curl_init($base . $chemin);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $methode,
        CURLOPT_HTTPHEADER => $entetes,
        CURLOPT_COOKIEJAR => $pot,
        CURLOPT_COOKIEFILE => $pot,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($corps !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corps, JSON_UNESCAPED_UNICODE));
    }
    $brut = curl_exec($ch);
    $statut = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur = (string) curl_error($ch);
    unset($ch); // curl_close() est obsolète depuis PHP 8.5 (sans effet depuis PHP 8.0).

    $decode = json_decode((string) $brut, true);

    return [
        'statut' => $statut,
        'corps' => is_array($decode) ? $decode : [],
        'brut' => (string) $brut,
        'erreur' => $erreur,
    ];
}

function pot(string $nom): string
{
    global $dossierCookies;
    return $dossierCookies . DIRECTORY_SEPARATOR . $nom . '.txt';
}

function jeton(string $pot): string
{
    $r = requete('GET', '/api/csrf', null, $pot);
    return (string) ($r['corps']['csrf'] ?? '');
}

function resumer(array $r): string
{
    $corps = $r['corps'] ? json_encode($r['corps'], JSON_UNESCAPED_UNICODE) : trim($r['brut']);
    if (mb_strlen($corps) > 220) {
        $corps = mb_substr($corps, 0, 217) . '...';
    }
    return 'HTTP ' . $r['statut'] . ' ' . $corps;
}

// ---------------------------------------------------------------------------
// 0. Le serveur répond-il ?
// ---------------------------------------------------------------------------
$sante = requete('GET', '/api/sante', null, pot('anon'));
if ($sante['statut'] !== 200) {
    fwrite(STDERR, "Le serveur $base ne répond pas (HTTP {$sante['statut']}).\n");
    fwrite(STDERR, "Lancez-le depuis VS Code : php -S 127.0.0.1:8001 -t TP07/public TP07/public/router.php\n");
    exit(1);
}
verifier(
    'Route de santé joignable',
    'ENF-01 disponibilité',
    'GET /api/sante',
    '200 et base joignable',
    resumer($sante),
    $sante['statut'] === 200 && ($sante['corps']['base'] ?? '') === 'joignable'
);

// ---------------------------------------------------------------------------
// 1. Comptes de test (inscription publique réelle)
// ---------------------------------------------------------------------------
$suffixe = date('YmdHis');
$emailA = "tp8.$suffixe.a@univ.test";
$emailB = "tp8.$suffixe.b@univ.test";
$motDePasse = 'MotDePasse123!';

$insA = requete('POST', '/api/inscription', [
    'nom' => 'TEST', 'prenom' => 'Alice', 'email' => $emailA,
    'role' => 'etudiant', 'motDePasse' => $motDePasse,
], pot('A'));
verifier(
    'Inscription de l\'étudiant A (compte créé)',
    'EF-01 inscription',
    "POST /api/inscription $emailA",
    '201',
    resumer($insA),
    $insA['statut'] === 201,
    'mot de passe haché vérifié plus loin (T-15)'
);

$insB = requete('POST', '/api/inscription', [
    'nom' => 'TEST', 'prenom' => 'Bruno', 'email' => $emailB,
    'role' => 'etudiant', 'motDePasse' => $motDePasse,
], pot('B'));
verifier(
    'Inscription de l\'étudiant B (compte créé)',
    'EF-01 inscription',
    "POST /api/inscription $emailB",
    '201',
    resumer($insB),
    $insB['statut'] === 201
);

$insDouble = requete('POST', '/api/inscription', [
    'nom' => 'TEST', 'prenom' => 'Alice', 'email' => $emailA,
    'role' => 'etudiant', 'motDePasse' => $motDePasse,
], pot('double'));
verifier(
    'Inscription avec un email déjà utilisé',
    'EF-01 unicité',
    "POST /api/inscription $emailA",
    '409',
    resumer($insDouble),
    $insDouble['statut'] === 409
);

$connexionFausse = requete('POST', '/api/connexion', [
    'email' => $emailA, 'motDePasse' => 'MauvaisMotDePasse!',
], pot('faux'));
verifier(
    'Connexion avec un mot de passe faux',
    'ENF-02 authentification',
    "POST /api/connexion $emailA / mauvais",
    '401 sans indiquer si le compte existe',
    resumer($connexionFausse),
    $connexionFausse['statut'] === 401
);

// Responsable fictif : session dédiée.
$connexionR = requete('POST', '/api/connexion', [
    'email' => 'sylvie.hounkpe@univ.test', 'motDePasse' => 'Responsable123!',
], pot('R'));
verifier(
    'Connexion du responsable (compte fictif)',
    'ENF-02 authentification',
    'POST /api/connexion sylvie.hounkpe@univ.test',
    '200',
    resumer($connexionR),
    $connexionR['statut'] === 200
);

$csrfA = jeton(pot('A'));
$csrfR = jeton(pot('R'));
verifier(
    'Jeton CSRF fourni par la session',
    'ENF-04 anti-CSRF',
    'GET /api/csrf',
    '200 avec un jeton non vide',
    'jeton de ' . strlen($csrfA) . ' caractères',
    $csrfA !== '' && strlen($csrfA) >= 32
);

$moi = requete('GET', '/api/moi', null, pot('A'));
verifier(
    'Profil de la session connectée',
    'EF-01 profil',
    'GET /api/moi (session A)',
    '200 avec email et jeton CSRF',
    resumer($moi),
    $moi['statut'] === 200 && ($moi['corps']['email'] ?? '') === $emailA && !empty($moi['corps']['csrf'])
);

// ---------------------------------------------------------------------------
// 2. Dépôt d'une demande par A
// ---------------------------------------------------------------------------
$creation = requete('POST', '/api/demandes', ['entrepriseId' => 1], pot('A'), $csrfA);
$idDemande = (int) ($creation['corps']['id'] ?? 0);
verifier(
    'Dépôt d\'une demande par l\'étudiant A',
    'EF-02 dépôt',
    'POST /api/demandes {entrepriseId:1}',
    '201 avec statut BROUILLON',
    resumer($creation),
    $creation['statut'] === 201 && ($creation['corps']['statut'] ?? '') === 'BROUILLON'
);

// ---------------------------------------------------------------------------
// 3. CSRF : écriture refusée sans jeton, acceptée avec jeton
// ---------------------------------------------------------------------------
$sansJeton = requete('PATCH', "/api/demandes/$idDemande/statut", ['statut' => 'SOUMISE'], pot('R'));
verifier(
    'Écriture sans en-tête X-CSRF-Token',
    'ENF-04 anti-CSRF',
    "PATCH /api/demandes/$idDemande/statut sans jeton",
    '403 et aucune modification',
    resumer($sansJeton),
    $sansJeton['statut'] === 403
);

$mauvaisJeton = requete('PATCH', "/api/demandes/$idDemande/statut", ['statut' => 'SOUMISE'], pot('R'), 'jeton-bidon');
verifier(
    'Écriture avec un jeton CSRF invalide',
    'ENF-04 anti-CSRF',
    "PATCH /api/demandes/$idDemande/statut jeton invalide",
    '403 et aucune modification',
    resumer($mauvaisJeton),
    $mauvaisJeton['statut'] === 403
);

$soumission = requete('PATCH', "/api/demandes/$idDemande/statut", ['statut' => 'SOUMISE'], pot('R'), $csrfR);
verifier(
    'Soumission de la demande (jeton valide)',
    'EF-03 transition',
    "PATCH /api/demandes/$idDemande/statut {SOUMISE}",
    '200 et statut SOUMISE',
    resumer($soumission),
    $soumission['statut'] === 200 && ($soumission['corps']['statut'] ?? '') === 'SOUMISE'
);

$decoSansJeton = requete('POST', '/api/deconnexion', null, pot('anon'));
verifier(
    'Déconnexion sans jeton CSRF',
    'ENF-04 anti-CSRF',
    'POST /api/deconnexion sans jeton',
    '403',
    resumer($decoSansJeton),
    $decoSansJeton['statut'] === 403
);

// ---------------------------------------------------------------------------
// 4. Cas limites
// ---------------------------------------------------------------------------
$inexistante = requete('PATCH', '/api/demandes/999999/statut', ['statut' => 'VALIDEE'], pot('R'), $csrfR);
verifier(
    'T-01 Demande inexistante',
    'EF-03 cas limite',
    'PATCH /api/demandes/999999/statut {VALIDEE}',
    '404',
    resumer($inexistante),
    $inexistante['statut'] === 404
);

$refusSansMotif = requete('PATCH', "/api/demandes/$idDemande/statut", ['statut' => 'REFUSEE'], pot('R'), $csrfR);
verifier(
    'T-02 Refus sans motif',
    'EF-03 motif obligatoire',
    "PATCH /api/demandes/$idDemande/statut {REFUSEE, motif vide}",
    '400',
    resumer($refusSansMotif),
    $refusSansMotif['statut'] === 400
);

$validation = requete('PATCH', "/api/demandes/$idDemande/statut", ['statut' => 'VALIDEE'], pot('R'), $csrfR);
verifier(
    'Validation de la demande',
    'EF-03 transition',
    "PATCH /api/demandes/$idDemande/statut {VALIDEE}",
    '200 et statut VALIDEE',
    resumer($validation),
    $validation['statut'] === 200 && ($validation['corps']['statut'] ?? '') === 'VALIDEE'
);

$validationRepetee = requete('PATCH', "/api/demandes/$idDemande/statut", ['statut' => 'VALIDEE'], pot('R'), $csrfR);
$detailApres = requete('GET', "/api/demandes/$idDemande", null, pot('R'));
verifier(
    'T-03 Validation répétée',
    'EF-03 cas limite',
    "PATCH /api/demandes/$idDemande/statut {VALIDEE} une seconde fois",
    '409 et statut inchangé (VALIDEE)',
    resumer($validationRepetee) . ' | statut en base : ' . ($detailApres['corps']['statut'] ?? '?'),
    $validationRepetee['statut'] === 409 && ($detailApres['corps']['statut'] ?? '') === 'VALIDEE'
);

$dateInvalide = requete('PATCH', "/api/demandes/$idDemande/soutenance", [
    'dateHeure' => '2026-02-30 10:00', 'salle' => 'B12',
], pot('R'), $csrfR);
verifier(
    'T-04 Date de soutenance invalide',
    'EF-05 cas limite',
    "PATCH /api/demandes/$idDemande/soutenance {2026-02-30 10:00}",
    '400 (date impossible au calendrier)',
    resumer($dateInvalide),
    $dateInvalide['statut'] === 400
);

$dateValide = requete('PATCH', "/api/demandes/$idDemande/soutenance", [
    'dateHeure' => '2026-12-11 10:00', 'salle' => 'B12',
], pot('R'), $csrfR);
verifier(
    'Soutenance valide programmée',
    'EF-05 programmation',
    "PATCH /api/demandes/$idDemande/soutenance {2026-12-11 10:00}",
    '200 avec date enregistrée',
    resumer($dateValide),
    $dateValide['statut'] === 200 && str_starts_with((string) ($dateValide['corps']['date_heure'] ?? ''), '2026-12-11')
);

$soutenanceNonValidee = requete('POST', '/api/demandes', ['entrepriseId' => 1], pot('A'), $csrfA);
$idBrouillon = (int) ($soutenanceNonValidee['corps']['id'] ?? 0);
$soutenanceSurBrouillon = requete('PATCH', "/api/demandes/$idBrouillon/soutenance", [
    'dateHeure' => '2026-12-12 09:00', 'salle' => 'C01',
], pot('R'), $csrfR);
verifier(
    'Soutenance refusée sur une demande non validée',
    'EF-05 règle métier',
    "PATCH /api/demandes/$idBrouillon/soutenance (demande BROUILLON)",
    '409',
    resumer($soutenanceSurBrouillon),
    $soutenanceSurBrouillon['statut'] === 409
);

// ---------------------------------------------------------------------------
// 5. Autorisations : B ne lit pas le dossier de A
// ---------------------------------------------------------------------------
$bLitA = requete('GET', "/api/demandes/$idDemande", null, pot('B'));
verifier(
    'Étudiant B lit le dossier de l\'étudiant A',
    'ENF-03 autorisation',
    "GET /api/demandes/$idDemande (session B)",
    '403 sans aucune donnée',
    resumer($bLitA),
    $bLitA['statut'] === 403 && !isset($bLitA['corps']['id'])
);

$aLitA = requete('GET', "/api/demandes/$idDemande", null, pot('A'));
verifier(
    'Étudiant A lit sa propre demande',
    'ENF-03 autorisation',
    "GET /api/demandes/$idDemande (session A)",
    '200',
    resumer($aLitA),
    $aLitA['statut'] === 200 && (int) ($aLitA['corps']['id'] ?? 0) === $idDemande
);

$rLitA = requete('GET', "/api/demandes/$idDemande", null, pot('R'));
verifier(
    'Le responsable lit n\'importe quelle demande',
    'ENF-03 autorisation',
    "GET /api/demandes/$idDemande (session responsable)",
    '200',
    resumer($rLitA),
    $rLitA['statut'] === 200
);

$listePersonnelParEtudiant = requete('GET', '/api/demandes', null, pot('A'));
verifier(
    'Étudiant sur la liste du personnel',
    'ENF-03 autorisation',
    'GET /api/demandes (session A)',
    '403',
    resumer($listePersonnelParEtudiant),
    $listePersonnelParEtudiant['statut'] === 403
);

$nonAuthentifie = requete('GET', '/api/demandes', null, pot('anon2'));
verifier(
    'Accès sans session',
    'ENF-02 authentification',
    'GET /api/demandes sans cookie de session',
    '401',
    resumer($nonAuthentifie),
    $nonAuthentifie['statut'] === 401
);

$mesDemandesB = requete('GET', '/api/demandes/mes', null, pot('B'));
$contientDemandeA = false;
foreach ((array) $mesDemandesB['corps'] as $ligne) {
    if ((int) ($ligne['id'] ?? 0) === $idDemande) {
        $contientDemandeA = true;
    }
}
verifier(
    'La liste « mes demandes » de B ne contient pas la demande de A',
    'ENF-03 cloisonnement',
    'GET /api/demandes/mes (session B)',
    '200 sans la demande de A',
    'demande A présente : ' . ($contientDemandeA ? 'oui' : 'non'),
    $mesDemandesB['statut'] === 200 && !$contientDemandeA
);

// ---------------------------------------------------------------------------
// 6. Injection SQL : « 1 OR 1=1 »
// ---------------------------------------------------------------------------
$injectionUrl = requete('GET', '/api/demandes/1%20OR%201%3D1', null, pot('R'));
verifier(
    'Injection « 1 OR 1=1 » dans l\'URL',
    'ENF-05 injection SQL',
    'GET /api/demandes/1 OR 1=1',
    '404 (aucune route, aucune fuite)',
    resumer($injectionUrl),
    $injectionUrl['statut'] === 404
);

$mesAvant = requete('GET', '/api/demandes/mes', null, pot('A'));
$injectionCorps = requete('POST', '/api/demandes', ['entrepriseId' => '1 OR 1=1'], pot('A'), $csrfA);
$mesApres = requete('GET', '/api/demandes/mes', null, pot('A'));
verifier(
    'Injection « 1 OR 1=1 » dans le corps JSON',
    'ENF-05 injection SQL',
    'POST /api/demandes {entrepriseId:"1 OR 1=1"}',
    '400, aucune ligne créée',
    resumer($injectionCorps) . ' | demandes avant/après : ' . count((array) $mesAvant['corps']) . '/' . count((array) $mesApres['corps']),
    $injectionCorps['statut'] === 400 && count((array) $mesAvant['corps']) === count((array) $mesApres['corps'])
);

$entrepriseInconnue = requete('POST', '/api/demandes', ['entrepriseId' => 999999], pot('A'), $csrfA);
verifier(
    'Dépôt avec une entreprise inexistante',
    'EF-02 validation',
    'POST /api/demandes {entrepriseId:999999}',
    '400',
    resumer($entrepriseInconnue),
    $entrepriseInconnue['statut'] === 400
);

$routeInconnue = requete('GET', '/api/inconnu', null, pot('anon2'));
verifier(
    'Route inconnue',
    'ENF-06 robustesse',
    'GET /api/inconnu',
    '404',
    resumer($routeInconnue),
    $routeInconnue['statut'] === 404
);

$detailInexistant = requete('GET', '/api/demandes/999999', null, pot('R'));
verifier(
    'Détail d\'une demande inexistante',
    'EF-03 cas limite',
    'GET /api/demandes/999999',
    '404',
    resumer($detailInexistant),
    $detailInexistant['statut'] === 404
);

// ---------------------------------------------------------------------------
// 7. Mots de passe hachés dans la base
// ---------------------------------------------------------------------------
$hashConforme = false;
$hashDescription = 'base injoignable';
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $stmt = $pdo->prepare('SELECT hash FROM utilisateur WHERE email = :e');
    $stmt->execute([':e' => $emailA]);
    $hash = (string) $stmt->fetchColumn();
    $hashConforme = str_starts_with($hash, '$2y$')
        && password_verify($motDePasse, $hash)
        && $hash !== $motDePasse
        && !str_contains($hash, $motDePasse);
    $hashDescription = 'algorithme ' . substr($hash, 0, 4) . ', longueur ' . strlen($hash)
        . ', identique au mot de passe : ' . ($hash === $motDePasse ? 'oui' : 'non');
} catch (Throwable $e) {
    $hashDescription = 'erreur base : ' . $e->getMessage();
}
verifier(
    'Mot de passe stocké haché (bcrypt)',
    'ENF-06 confidentialité',
    "SELECT hash FROM utilisateur WHERE email = $emailA",
    'hash bcrypt ($2y$), password_verify() vrai, jamais le mot de passe en clair',
    $hashDescription,
    $hashConforme
);

// ---------------------------------------------------------------------------
// 8. Nettoyage : les données de test ne restent pas dans la base
// ---------------------------------------------------------------------------
$nettoyage = 'non effectué';
try {
    if (isset($pdo)) {
        $ids = array_values(array_filter([$idDemande, $idBrouillon], static fn (int $i): bool => $i > 0));
        if ($ids !== []) {
            $marques = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM soutenance WHERE demande_id IN ($marques)")->execute($ids);
            $pdo->prepare("DELETE FROM historique WHERE demande_id IN ($marques)")->execute($ids);
            $pdo->prepare("DELETE FROM demande_stage WHERE id IN ($marques)")->execute($ids);
        }
        $pdo->prepare('DELETE FROM etudiant WHERE email IN (?, ?)')->execute([$emailA, $emailB]);
        $pdo->prepare('DELETE FROM utilisateur WHERE email IN (?, ?)')->execute([$emailA, $emailB]);
        $nettoyage = 'comptes et demandes de test supprimés';
    }
} catch (Throwable $e) {
    $nettoyage = 'échec du nettoyage : ' . $e->getMessage();
}

// ---------------------------------------------------------------------------
// 9. Écriture des preuves
// ---------------------------------------------------------------------------
$conformes = count(array_filter($resultats, static fn (array $r): bool => $r['conforme'] === 'OUI'));
$total = count($resultats);
$horodatage = date('d/m/Y H:i:s');

$lignes = [];
$lignes[] = 'TP08 - PLAN DE TESTS ET AUDIT DE SÉCURITÉ';
$lignes[] = '=========================================';
$lignes[] = "Date d'exécution : $horodatage";
$lignes[] = "Serveur : $base";
$lignes[] = "Base : $dbName (port $dbPort)";
$lignes[] = "Résultat : $conformes/$total cas conformes";
$lignes[] = "Nettoyage : $nettoyage";
$lignes[] = '';
$lignes[] = '| # | Cas | Exigence | Données | Attendu | Observé | Conforme |';
$lignes[] = '|---|-----|----------|---------|---------|---------|----------|';
foreach ($resultats as $r) {
    $lignes[] = '| ' . $r['numero'] . ' | ' . $r['cas'] . ' | ' . $r['exigence'] . ' | '
        . str_replace('|', '\\|', $r['donnees']) . ' | ' . str_replace('|', '\\|', $r['attendu']) . ' | '
        . str_replace('|', '\\|', $r['observe']) . ' | ' . $r['conforme'] . ' |';
}

file_put_contents($dossierPreuves . '/resultats_tests.txt', implode(PHP_EOL, $lignes) . PHP_EOL);
file_put_contents(
    $dossierPreuves . '/resultats_tests.json',
    json_encode(['date' => $horodatage, 'serveur' => $base, 'base' => $dbName, 'resultats' => $resultats], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
);

echo PHP_EOL . "Résultat : $conformes/$total cas conformes" . PHP_EOL;
echo 'Preuves écrites dans ' . $dossierPreuves . PHP_EOL;

exit($conformes === $total ? 0 : 1);
