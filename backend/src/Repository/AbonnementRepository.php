<?php

namespace App\Repository;

use App\Entity\Abonnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    public function findActifs(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.actif = true')
            ->orderBy('a.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }
}