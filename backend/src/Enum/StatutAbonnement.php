<?php

namespace App\Enum;

enum StatutAbonnement: string
{
    case ESSAI = 'essai';
    case ACTIF = 'actif';
    case EXPIRE = 'expire';
    case ANNULE = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::ESSAI => 'Période d’essai',
            self::ACTIF => 'Abonnement actif',
            self::EXPIRE => 'Abonnement expiré',
            self::ANNULE => 'Abonnement annulé',
        };
    }
}