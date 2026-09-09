<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByUtilisateur(int $utilisateurId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.utilisateur = :utilisateurId')
            ->setParameter('utilisateurId', $utilisateurId)
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReference(string $reference): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.reference = :reference')
            ->setParameter('reference', $reference)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllFiltered(?string $role = null, ?string $motif = null, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.utilisateur', 'u')
            ->addSelect('u')
            ->orderBy('t.dateCreation', 'DESC');
        if ($userId !== null) {
            $qb->andWhere('t.utilisateur = :userId')->setParameter('userId', $userId);
        }
        if ($role !== null) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }
        if ($motif !== null) {
            $qb->andWhere('t.motif = :motif')->setParameter('motif', $motif);
        }
        return $qb->getQuery()->getResult();
    }
}