<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Repositories/DemandeRepository.php';
require_once dirname(__DIR__) . '/src/Services/DemandeService.php';

/** Test de la table des transitions, sans base de données. */
$cas = [
    ['BROUILLON', 'SOUMISE', true],
    ['SOUMISE', 'VALIDEE', true],
    ['SOUMISE', 'REFUSEE', true],
    ['REFUSEE', 'BROUILLON', true],
    ['VALIDEE', 'REFUSEE', false],   // transition interdite -> 409
    ['VALIDEE', 'SOUMISE', false],
    ['REFUSEE', 'VALIDEE', false],
];

$ok = true;
foreach ($cas as [$avant, $apres, $attendu]) {
    $obtenu = DemandeService::transitionEstAutorisee($avant, $apres);
    $statut = $obtenu === $attendu ? 'OK' : 'ECHEC';
    if ($statut === 'ECHEC') {
        $ok = false;
    }
    printf("%-4s %-10s -> %-10s : %s (attendu %s)\n", $statut, $avant, $apres, var_export($obtenu, true), var_export($attendu, true));
}

exit($ok ? 0 : 1);
