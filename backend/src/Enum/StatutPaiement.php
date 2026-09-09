<?php

namespace App\Enum;

enum StatutPaiement: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDE = 'valide';
    case ECHOUE = 'echoue';
    case REMBOURSE = 'rembourse';
}
