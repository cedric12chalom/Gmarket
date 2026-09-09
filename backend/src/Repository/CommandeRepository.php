<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findByAcheteur(int $acheteurId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.acheteur = :acheteurId')
            ->setParameter('acheteurId', $acheteurId)
            ->getQuery()
            ->getResult();
    }
}
