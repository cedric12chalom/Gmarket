<?php

namespace App\Service;

/**
 * Commission prélevée sur les paiements de la plateforme.
 *
 * V1 : taux réduit (la plateforme est compensée en partie par les revenus
 * d'abonnement). Le taux exact est à figer dans le business plan ; cette
 * classe centralise la valeur pour n'avoir qu'un seul endroit à modifier.
 */
class CommissionService
{
    public const TAUX_COMMISSION = 0.05; // 5% — taux réduit

    public function calculerCommission(float $montant): float
    {
        return round($montant * self::TAUX_COMMISSION, 2);
    }
}