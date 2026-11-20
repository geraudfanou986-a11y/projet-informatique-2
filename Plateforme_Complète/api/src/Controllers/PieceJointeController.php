<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/http.php';
require_once __DIR__ . '/../Exceptions/HttpException.php';

/** Couche HTTP des documents joints : envoi multipart, lecture du fichier, suppression. */
final class PieceJointeController
{
    public function __construct(
        private PieceJointeService $service,
        private AuthService $auth
    ) {
    }

    /** POST multipart/form-data avec un champ nommé « piece ». */
    public function envoyer(int $id): never
    {
        $u = $this->auth->exigeRole(['etudiant']);
        repondre(201, $this->service->envoyer($id, $_FILES['piece'] ?? [], $u));
    }

    /** GET : téléchargement forcé, ou affichage en ligne pour les images (?apercu=1). */
    public function telecharger(int $id): never
    {
        $u = $this->auth->utilisateur();
        $piece = $this->service->obtenir($id, $u);
        $apercu = ($_GET['apercu'] ?? '') === '1' && str_starts_with($piece['type'], 'image/');

        header('Content-Type: ' . $piece['type']);
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . (string) $piece['taille']);
        header('Cache-Control: private, no-store');
        header('Content-Disposition: ' . ($apercu ? 'inline' : 'attachment')
            . '; filename="' . $this->nomAscii($piece['nom']) . '"'
            . '; filename*=UTF-8\'\'' . rawurlencode($piece['nom']));
        readfile($piece['chemin']);
        exit;
    }

    public function supprimer(int $id): never
    {
        $u = $this->auth->exigeRole(['etudiant']);
        repondre(200, $this->service->supprimer($id, $u));
    }

    /** Nom ASCII de secours pour les navigateurs anciens (le nom UTF-8 reste prioritaire). */
    private function nomAscii(string $nom): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $nom) ?: 'document';

        return str_replace(['"', '\\', "\r", "\n"], '_', $ascii);
    }
}
