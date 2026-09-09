<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Récupère tous les produits avec leurs relations (categorie, varietes)
     * en eager-loading pour éviter les N+1 sur le catalogue complet.
     *
     * @return Produit[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('c', 'v')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('p.varietes', 'v')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
