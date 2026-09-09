<?php

namespace App\Enum;

enum IndiceFraicheur: string
{
    case TRES_FRAIS = 'tres_frais';
    case FRAIS = 'frais';
    case A_CONSOMMER = 'a_consommer';
    case EXPIRE = 'expire';
}
