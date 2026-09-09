<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Commandes d'un acheteur (avec lignes + produits).
     */
    public function findByAcheteur(int $acheteurId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.acheteur = :acheteurId')
            ->setParameter('acheteurId', $acheteurId)
            ->leftJoin('c.lignes', 'l')
            ->addSelect('l')
            ->leftJoin('l.produit', 'p')
            ->addSelect('p')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes d'une boutique (dashboard vendeur).
     */
    public function findByBoutique(int $boutiqueId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.boutique = :boutiqueId')
            ->setParameter('boutiqueId', $boutiqueId)
            ->leftJoin('c.lignes', 'l')
            ->addSelect('l')
            ->leftJoin('l.produit', 'p')
            ->addSelect('p')
            ->leftJoin('c.acheteur', 'a')
            ->addSelect('a')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}