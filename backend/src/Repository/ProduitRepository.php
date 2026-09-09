<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Produits actifs avec chargement des dépendances.
     *
     * @return array<int, Produit>
     */
    public function findActifs(?int $categorieId = null, ?int $boutiqueId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.actif = true')
            ->leftJoin('p.variantes', 'v')
            ->addSelect('v')
            ->leftJoin('p.offre', 'o')
            ->addSelect('o')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->leftJoin('p.boutique', 'b')
            ->addSelect('b')
            ->orderBy('p.createdAt', 'DESC');

        if ($categorieId !== null) {
            $qb->andWhere('p.categorie = :categorieId')->setParameter('categorieId', $categorieId);
        }
        if ($boutiqueId !== null) {
            $qb->andWhere('p.boutique = :boutiqueId')->setParameter('boutiqueId', $boutiqueId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Produits d'une boutique (actifs ou non) — dashboard vendeur.
     *
     * @return array<int, Produit>
     */
    public function findByBoutique(int $boutiqueId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.boutique = :boutiqueId')
            ->setParameter('boutiqueId', $boutiqueId)
            ->leftJoin('p.variantes', 'v')
            ->addSelect('v')
            ->leftJoin('p.offre', 'o')
            ->addSelect('o')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}