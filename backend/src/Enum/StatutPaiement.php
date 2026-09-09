<?php

namespace App\Enum;

enum StatutPaiement: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDE = 'valide';
    case ECHOUE = 'echoue';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de paiement',
            self::VALIDE => 'Paiement validé',
            self::ECHOUE => 'Paiement échoué',
        };
    }
}