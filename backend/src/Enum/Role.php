<?php

namespace App\Enum;

enum Role: string
{
    case ADMIN = 'ROLE_ADMIN';
    case VENDEUR = 'ROLE_VENDEUR';
    case ACHETEUR = 'ROLE_ACHETEUR';

    /**
     * Rôle de plus haut niveau parmi un ensemble de rôles.
     */
    public static function plusEleve(array $roles): self
    {
        $priority = [self::ACHETEUR->value => 1, self::VENDEUR->value => 2, self::ADMIN->value => 3];
        $best = self::ACHETEUR;
        foreach ($roles as $role) {
            if (isset($priority[$role]) && $priority[$role] > $priority[$best->value]) {
                $best = self::from($role);
            }
        }
        return $best;
    }
}