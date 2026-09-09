<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Avis publics d'une boutique.
     */
    public function findByBoutique(int $boutiqueId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.boutique = :boutiqueId')
            ->setParameter('boutiqueId', $boutiqueId)
            ->leftJoin('a.acheteur', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function moyenneNote(int $boutiqueId): ?float
    {
        return $this->createQueryBuilder('a')
            ->select('AVG(a.note)')
            ->andWhere('a.boutique = :boutiqueId')
            ->setParameter('boutiqueId', $boutiqueId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}