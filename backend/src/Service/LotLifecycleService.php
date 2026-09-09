<?php

namespace App\Service;

use App\Entity\Lot;
use App\Enum\StatutLot;
use Doctrine\ORM\EntityManagerInterface;

class LotLifecycleService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Retire du catalogue tous les lots encore DISPONIBLE dont le délai de
     * retrait (1 jour après la date d'expiration) est atteint ou dépassé.
     * Retourne le nombre de lots retirés.
     */
    public function retirerLotsExpires(): int
    {
        $lots = $this->em->getRepository(Lot::class)->findAll();
        $count = 0;

        foreach ($lots as $lot) {
            if ($lot->getStatut() !== StatutLot::DISPONIBLE->value) {
                continue;
            }
            $joursAvantRetrait = $lot->getJoursAvantRetrait();
            if ($joursAvantRetrait === null || $joursAvantRetrait > 0) {
                continue;
            }
            $lot->retirer();
            $count++;
        }

        if ($count > 0) {
            $this->em->flush();
        }

        return $count;
    }
}
