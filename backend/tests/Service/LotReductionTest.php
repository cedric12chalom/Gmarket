<?php

namespace App\Tests\Service;

use App\Entity\Lot;
use PHPUnit\Framework\TestCase;

/**
 * Couvre la règle métier : une commande créée sur un lot bientôt périmé
 * doit être facturée au prix réduit (-30%), comme annoncé au catalogue
 * (prix_reduit / prix_acheteur dans EntitySerializer::lot()).
 */
class LotReductionTest extends TestCase
{
    private function creerLot(int $joursAvantExpiration, float $prix): Lot
    {
        $lot = new Lot();
        $lot->setPrixProducteur((string) $prix);
        $lot->setDureeConservation(5);
        $lot->setDateRecolte((new \DateTime('today'))->modify(($joursAvantExpiration - 5) . ' days'));
        return $lot;
    }

    public function testPrixReduitAppliqueSurLotPerimantAujourdhui(): void
    {
        $lot = $this->creerLot(0, 100.0);
        $this->assertSame(70.0, $lot->calculerPrixReduit());
    }

    public function testPrixReduitNullHorsFenetreDePeremption(): void
    {
        $this->assertNull($this->creerLot(3, 100.0)->calculerPrixReduit());
        $this->assertNull($this->creerLot(-2, 100.0)->calculerPrixReduit());
    }

    public function testCalculPrixCommandeUtiliseLePrixReduit(): void
    {
        $lot = $this->creerLot(0, 100.0);
        $prixBase = (float) ($lot->calculerPrixReduit() ?? $lot->getPrixProducteur());
        $this->assertSame(70.0, $prixBase);

        $lotSain = $this->creerLot(3, 100.0);
        $prixBaseSain = (float) ($lotSain->calculerPrixReduit() ?? $lotSain->getPrixProducteur());
        $this->assertSame(100.0, $prixBaseSain);
    }
}
