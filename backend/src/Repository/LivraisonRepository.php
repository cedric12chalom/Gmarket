<?php

namespace App\Repository;

use App\Entity\Livraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livraison::class);
    }

    public function findByLivreur(int $livreurId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.livreur = :livreurId')
            ->setParameter('livreurId', $livreurId)
            ->getQuery()
            ->getResult();
    }

    public function findOneByCommande(int $commandeId): ?Livraison
    {
        return $this->findOneBy(['commande' => $commandeId]);
    }

    /**
     * Livraisons pas encore prises en charge par un livreur : disponibles pour être choisies.
     */
    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.livreur IS NULL')
            ->andWhere('l.statut = :statut')
            ->setParameter('statut', \App\Enum\StatutLivraison::ASSIGNEE->value)
            ->getQuery()
            ->getResult();
    }
}

