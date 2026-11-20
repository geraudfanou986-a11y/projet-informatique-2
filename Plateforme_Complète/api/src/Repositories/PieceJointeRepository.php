<?php
declare(strict_types=1);

/** Seule couche SQL pour les documents joints : requêtes préparées, aucune concaténation. */
final class PieceJointeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Métadonnées du document d'une demande, ou null s'il n'y en a pas. */
    public function pour(int $demandeId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT demande_id, nom_stockage, nom_original, type_mime, taille, cree_le
             FROM piece_jointe WHERE demande_id = :id'
        );
        $stmt->execute([':id' => $demandeId]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $ligne;
    }

    /** Crée ou remplace le document d'une demande (une seule ligne par demande). */
    public function enregistrer(
        int $demandeId,
        string $nomStockage,
        string $nomOriginal,
        string $typeMime,
        int $taille
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO piece_jointe (demande_id, nom_stockage, nom_original, type_mime, taille)
             VALUES (:d, :s, :o, :t, :z)
             ON DUPLICATE KEY UPDATE nom_stockage = VALUES(nom_stockage),
                                    nom_original = VALUES(nom_original),
                                    type_mime = VALUES(type_mime),
                                    taille = VALUES(taille),
                                    cree_le = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            ':d' => $demandeId, ':s' => $nomStockage, ':o' => $nomOriginal,
            ':t' => $typeMime, ':z' => $taille,
        ]);
    }

    /** Supprime la ligne et renvoie l'ancien nom de fichier pour nettoyage sur disque. */
    public function supprimer(int $demandeId): ?array
    {
        $ancien = $this->pour($demandeId);
        if ($ancien === null) {
            return null;
        }
        $stmt = $this->pdo->prepare('DELETE FROM piece_jointe WHERE demande_id = :id');
        $stmt->execute([':id' => $demandeId]);

        return $ancien;
    }
}
