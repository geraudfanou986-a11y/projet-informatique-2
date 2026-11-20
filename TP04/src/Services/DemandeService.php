<?php
declare(strict_types=1);

/**
 * Couche métier : elle porte les règles de validation et les transitions.
 * Elle ne connaît ni HTTP ni SQL, seulement le dépôt reçu en dépendance.
 * Les règles de transition seront implémentées et testées en TP06.
 */
final class DemandeService
{
    public function __construct(private DemandeRepository $repository)
    {
    }
}
