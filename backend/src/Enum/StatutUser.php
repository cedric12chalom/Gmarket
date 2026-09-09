<?php

namespace App\Enum;

enum StatutUser: string
{
    case ACTIF = 'actif';
    case SUSPENDU = 'suspendu';
    case INACTIF = 'inactif';
}
