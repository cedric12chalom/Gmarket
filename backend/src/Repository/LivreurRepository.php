<?php

namespace App\Repository;

use App\Entity\Livreur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LivreurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livreur::class);
    }

    /**
     * Liste les livreurs actifs, disponibles, dont le dossier de vérification est validé
     * et dont le type de transport correspond.
     */
    public function findEligible(string $typeTransport): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.statut = :statut')
            ->andWhere('l.disponible = :disponible')
            ->andWhere('l.typeTransport = :typeTransport')
            ->andWhere('l.statutVerification = :statutVerification')
            ->setParameter('statut', \App\Enum\StatutUser::ACTIF->value)
            ->setParameter('disponible', true)
            ->setParameter('typeTransport', $typeTransport)
            ->setParameter('statutVerification', \App\Enum\StatutVerificationLivreur::VALIDE->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste les livreurs dont le statut de vérification est dans la liste donnée.
     */
    public function findByStatutVerification(array $statuts): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.statutVerification IN (:statuts)')
            ->setParameter('statuts', $statuts)
            ->orderBy('l.dateInscription', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
