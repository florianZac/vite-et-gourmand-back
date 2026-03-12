<?php

namespace App\Enum;

/**
 * @author      Florian Aizac
 * @created     01/03/2026
 * @description Constantes des statuts de commande
 * Centralise toutes les valeurs de statut pour éviter les chaînes en dur dans les controllers
 */
class CommandeStatut
{
    public const EN_ATTENTE                 = 'En attente';
    public const ACCEPTEE                   = 'Acceptée';
    public const EN_PREPARATION             = 'En préparation';
    public const EN_LIVRAISON               = 'En livraison';
    public const LIVRE                      = 'Livré';
    public const EN_ATTENTE_RETOUR_MATERIEL = 'En attente du retour matériel';
    public const TERMINEE                   = 'Terminée';
    public const ANNULEE                    = 'annulée';

    // Statuts dans lesquels un CLIENT peut modifier sa commande
    public const MODIFIABLES = [
        self::EN_ATTENTE, // seulement avant acceptation
    ];

    // Statuts dans lesquels un CLIENT peut annuler sa commande
    public const ANNULABLES_CLIENT = [
        self::EN_ATTENTE,
        self::ACCEPTEE,
        self::EN_PREPARATION,
    ];

    // Ordre strict du cycle de vie pour les employés
    public const ORDRE = [
        self::EN_ATTENTE                 => 1,
        self::ACCEPTEE                   => 2,
        self::EN_PREPARATION             => 3,
        self::EN_LIVRAISON               => 4,
        self::LIVRE                      => 5,
        self::EN_ATTENTE_RETOUR_MATERIEL => 6,
        self::TERMINEE                   => 7,
    ];
}