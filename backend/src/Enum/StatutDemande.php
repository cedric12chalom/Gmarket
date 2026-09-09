<?php

namespace App\Enum;

enum StatutDemande: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVEE = 'approuvee';
    case REFUSEE = 'refusee';
}
