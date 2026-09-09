<?php

namespace App\Enum;

enum StatutLot: string
{
    case DISPONIBLE = 'disponible';
    case EPUISE = 'epuise';
    case EXPIRE = 'expire';
    case RETIRE = 'retire';
}
