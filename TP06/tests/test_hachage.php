<?php
declare(strict_types=1);

/** Vérifie que les hashes du SQL correspondent aux mots de passe fictifs annoncés. */
$comptes = [
    ['Etudiant123!',    '$2y$12$Q5c.v2gSqOlk8EyNLMqJQuopgOyCMSLbzirZJVF7eBuXMdmmMp8JS'],
    ['Responsable123!', '$2y$12$S.0qZ52hRFPfbP8l7s2CnOpDEHe6hxqU2bY1GSWkEmW0K/FIjbX16'],
    ['Secretariat123!', '$2y$12$HIJdQevwpjyHu14/MJI8fe5FNjmKRbwiF.AZgm/DNgJPKsTs0XIe.'],
];

$ok = true;
foreach ($comptes as [$clair, $hash]) {
    $valide = password_verify($clair, $hash);
    printf("%-4s %-18s : password_verify = %s\n", $valide ? 'OK' : 'ECHEC', $clair, $valide ? 'true' : 'false');
    $ok = $ok && $valide;
}

// Un mot de passe erroné doit toujours être refusé.
$faux = password_verify('mauvais-mot-de-passe', $comptes[0][1]);
printf("%-4s mot de passe erronne : password_verify = %s\n", $faux ? 'ECHEC' : 'OK', var_export($faux, true));
$ok = $ok && !$faux;

exit($ok ? 0 : 1);
