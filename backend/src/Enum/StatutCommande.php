<?php

namespace App\Enum;

enum StatutCommande: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRMEE = 'confirmee';
    case PAYEE = 'payee';
    case EN_COURS = 'en_cours';
    case LIVREE = 'livree';
    case ANNULEE = 'annulee';
}
