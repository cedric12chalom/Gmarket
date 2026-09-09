<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByLivraison(int $livraisonId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.livraison = :livraisonId')
            ->setParameter('livraisonId', $livraisonId)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countNonLusByLivraisonAndDestinataire(int $livraisonId, int $destinataireId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.livraison = :livraisonId')
            ->andWhere('m.expediteur != :destinataireId')
            ->andWhere('m.lu = false')
            ->setParameter('livraisonId', $livraisonId)
            ->setParameter('destinataireId', $destinataireId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
