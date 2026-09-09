<?php

namespace App\Repository;

use App\Entity\Boutique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boutique>
 */
class BoutiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boutique::class);
    }

    public function findOneBySlug(string $slug): ?Boutique
    {
        return $this->createQueryBuilder('b')
            ->andWhere('LOWER(b.slug) = :slug')
            ->setParameter('slug', strtolower(trim($slug)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche de vendeurs par nom de boutique ou pseudo TikTok.
     *
     * @return array<int, Boutique>
     */
    public function search(string $q): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('LOWER(b.nom) LIKE :q OR LOWER(b.tiktokPseudo) LIKE :q')
            ->setParameter('q', '%' . strtolower(trim($q)) . '%')
            ->orderBy('b.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}