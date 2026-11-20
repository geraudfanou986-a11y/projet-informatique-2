<?php
declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/HttpException.php';

/**
 * Documents joints d'une demande : validation réelle du fichier, stockage hors
 * du dossier public/ et contrôle d'accès réutilisé depuis DemandeService.
 *
 * Formats acceptés : PDF, JPEG, PNG. Limite : 2 Mo (valeur par défaut de PHP).
 * Le type MIME est lu dans le contenu du fichier (finfo), jamais dans le nom
 * fourni par le navigateur : un .html renommé .png est donc refusé.
 */
final class PieceJointeService
{
    public const TAILLE_MAX = 2 * 1024 * 1024;

    /** Extensions autorisées => type MIME attendu, vérifié avec finfo. */
    private const TYPES = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    public function __construct(
        private PieceJointeRepository $repository,
        private DemandeService $demandes
    ) {
    }

    /** Dossier de stockage, hors de public/ : le routeur ne peut pas le servir. */
    private function dossier(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'stockage';
    }

    /**
     * Le contrôle de lecture (403 pour un autre étudiant) est fait par detail().
     * Seul l'étudiant propriétaire peut écrire : le personnel lit, ne modifie pas.
     */
    private function exigeProprietaire(int $id, array $utilisateur): array
    {
        $demande = $this->demandes->detail($id, $utilisateur);
        if (($utilisateur['role'] ?? '') !== 'etudiant'
            || (int) $demande['etudiant_id'] !== (int) ($utilisateur['etudiant_id'] ?? 0)) {
            throw new HttpException(403, 'Seul l’étudiant concerné peut modifier un document');
        }

        return $demande;
    }

    /** Ajoute ou remplace le document d'une demande. */
    public function envoyer(int $id, array $fichier, array $utilisateur): array
    {
        $this->exigeProprietaire($id, $utilisateur);

        $erreur = (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erreur === UPLOAD_ERR_NO_FILE) {
            throw new HttpException(400, 'Aucun fichier sélectionné');
        }
        if ($erreur === UPLOAD_ERR_INI_SIZE || $erreur === UPLOAD_ERR_FORM_SIZE) {
            throw new HttpException(400, 'Fichier trop volumineux (limite PHP)');
        }
        if ($erreur !== UPLOAD_ERR_OK
            || !is_string($fichier['tmp_name'] ?? null)
            || !is_uploaded_file($fichier['tmp_name'])) {
            throw new HttpException(400, 'Transfert du fichier impossible');
        }

        $taille = (int) ($fichier['size'] ?? 0);
        if ($taille <= 0 || $taille > self::TAILLE_MAX) {
            throw new HttpException(400, 'Document trop volumineux (2 Mo maximum)');
        }

        $nomFourni = str_replace('\\', '/', (string) ($fichier['name'] ?? ''));
        $extension = strtolower((string) pathinfo($nomFourni, PATHINFO_EXTENSION));
        $typeMime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
        if (!isset(self::TYPES[$extension]) || self::TYPES[$extension] !== $typeMime) {
            throw new HttpException(400, 'Format refusé : utilisez un PDF, un JPG ou un PNG');
        }

        // Nom d'affichage nettoyé ; le nom sur disque est tiré au sort.
        $nomOriginal = basename($nomFourni);
        $nomOriginal = preg_replace('/[\x00-\x1F\x7F]/', '', $nomOriginal) ?: 'document';
        if (mb_strlen($nomOriginal) > 255) {
            $nomOriginal = mb_substr($nomOriginal, 0, 255);
        }

        $ancien = $this->repository->pour($id);
        $nomStockage = bin2hex(random_bytes(16)) . '.' . $extension;
        $dossier = $this->dossier();
        if (!is_dir($dossier) && !mkdir($dossier, 0700, true) && !is_dir($dossier)) {
            throw new HttpException(500, 'Dossier de stockage indisponible');
        }
        $chemin = $dossier . DIRECTORY_SEPARATOR . $nomStockage;
        if (!move_uploaded_file($fichier['tmp_name'], $chemin)) {
            throw new HttpException(500, 'Enregistrement du document impossible');
        }
        @chmod($chemin, 0600); // Sans effet sous Windows : on continue.

        try {
            $this->repository->enregistrer($id, $nomStockage, $nomOriginal, $typeMime, $taille);
        } catch (Throwable $e) {
            @unlink($chemin); // On ne garde pas un fichier orphelin.
            throw $e;
        }
        if ($ancien !== null && $ancien['nom_stockage'] !== $nomStockage) {
            @unlink($dossier . DIRECTORY_SEPARATOR . $ancien['nom_stockage']);
        }

        return $this->presentation($this->repository->pour($id) ?? []);
    }

    /** Métadonnées renvoyées à l'interface : jamais le nom physique sur disque. */
    private function presentation(array $piece): array
    {
        if ($piece === []) {
            return [];
        }

        return [
            'nom'     => $piece['nom_original'],
            'type'    => $piece['type_mime'],
            'taille'  => (int) $piece['taille'],
            'cree_le' => $piece['cree_le'] ?? null,
        ];
    }

    /** Informations nécessaires au téléchargement, avec contrôle d'accès. */
    public function obtenir(int $id, array $utilisateur): array
    {
        $this->demandes->detail($id, $utilisateur);
        $piece = $this->repository->pour($id);
        if ($piece === null) {
            throw new HttpException(404, 'Aucun document joint à cette demande');
        }
        $chemin = $this->dossier() . DIRECTORY_SEPARATOR . $piece['nom_stockage'];
        if (!is_file($chemin)) {
            throw new HttpException(404, 'Document introuvable sur le serveur');
        }

        return [
            'chemin' => $chemin,
            'nom'    => $piece['nom_original'],
            'type'   => $piece['type_mime'],
            'taille' => (int) $piece['taille'],
        ];
    }

    /** Retire le document (étudiant propriétaire uniquement). */
    public function supprimer(int $id, array $utilisateur): array
    {
        $this->exigeProprietaire($id, $utilisateur);
        $ancien = $this->repository->supprimer($id);
        if ($ancien === null) {
            throw new HttpException(404, 'Aucun document joint à cette demande');
        }
        @unlink($this->dossier() . DIRECTORY_SEPARATOR . $ancien['nom_stockage']);

        return ['supprime' => true];
    }
}
