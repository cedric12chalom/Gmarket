<?php

namespace App\Enum;

enum StatutLivraison: string
{
    case ASSIGNEE = 'assignee';
    case EN_COURS = 'en_cours';
    case LIVREE = 'livree';
    case ECHOUEE = 'echouee';
}
