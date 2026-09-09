<?php

namespace App\Repository;

use App\Entity\HistoriquePrix;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HistoriquePrixRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriquePrix::class);
    }

    public function findByProduit(int $produitId, int $limite = 30): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.produit = :produitId')
            ->setParameter('produitId', $produitId)
            ->orderBy('h.dateReleve', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    public function findByProduitAndDate(int $produitId, \DateTimeInterface $date): ?HistoriquePrix
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.produit = :produitId')
            ->andWhere('h.dateReleve = :date')
            ->setParameter('produitId', $produitId)
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}