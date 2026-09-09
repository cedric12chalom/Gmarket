<?php

namespace App\Enum;

enum StatutCommande: string
{
    case EN_ATTENTE = 'en_attente';
    case EXPEDIEE = 'expediee';
    case LIVREE = 'livree';
    case ANNULEE = 'annulee';

    /**
     * Les avis ne sont ouverts que pour les commandes livrées.
     */
    public function ouvreAvis(): bool
    {
        return $this === self::LIVREE;
    }
}