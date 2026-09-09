<?php

namespace App\Enum;

enum StatutVerificationLivreur: string
{
    case EN_ATTENTE = 'en_attente';
    case VERIFIE = 'verifie';
    case VALIDE = 'valide';
    case REFUSE = 'refuse';
}
