<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findNonLuesByUtilisateur(int $utilisateurId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.utilisateur = :utilisateurId')
            ->andWhere('n.lu = false')
            ->setParameter('utilisateurId', $utilisateurId)
            ->getQuery()
            ->getResult();
    }
}
