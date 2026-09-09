<?php

namespace App\Repository;

use App\Entity\Variante;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Variante>
 */
class VarianteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Variante::class);
    }

    public function findByProduit(int $produitId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.produit = :produitId')
            ->setParameter('produitId', $produitId)
            ->orderBy('v.couleur', 'ASC')
            ->addOrderBy('v.taille', 'ASC')
            ->getQuery()
            ->getResult();
    }
}