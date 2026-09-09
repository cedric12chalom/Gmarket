<?php

namespace App\Enum;

enum MoyenDeplacement: string
{
    case PIED = 'pied';
    case VELO = 'velo';
    case MOTO = 'moto';
    case VOITURE = 'voiture';
    case CAMIONNETTE = 'camionnette';
}
