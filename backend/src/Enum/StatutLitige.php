<?php

namespace App\Enum;

enum StatutLitige: string
{
    case OUVERT = 'ouvert';
    case EN_TRAITEMENT = 'en_traitement';
    case RESOLU = 'resolu';
    case REJETE = 'rejete';
}
