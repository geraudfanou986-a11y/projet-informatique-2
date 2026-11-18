<?php
declare(strict_types=1);

/**
 * Couche HTTP : elle lit la requête et écrit la réponse.
 * Aucune règle métier ni requête SQL ici ; le service sera appelé en TP6.
 */
final class DemandeController
{
    public function __construct(private DemandeService $service)
    {
    }
}
