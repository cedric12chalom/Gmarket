<?php

namespace App\Enum;

enum Role: string
{
    case PRODUCTEUR = 'producteur';
    case ACHETEUR = 'acheteur';
    case LIVREUR = 'livreur';
    case ADMIN = 'admin';
}
