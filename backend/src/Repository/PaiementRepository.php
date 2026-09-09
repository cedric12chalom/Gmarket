<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    public function findOneByCommande(int $commandeId): ?Paiement
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.commande = :commandeId')
            ->setParameter('commandeId', $commandeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Un paiement "bloquant" empêche d'en recréer un nouveau pour la même commande :
     * soit déjà validé, soit encore en attente de confirmation du fournisseur. Un paiement
     * échoué ne bloque pas : l'acheteur doit pouvoir réessayer.
     */
    public function findBloquantByCommande(int $commandeId): ?Paiement
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.commande = :commandeId')
            ->andWhere('p.statut IN (:statuts)')
            ->setParameter('commandeId', $commandeId)
            ->setParameter('statuts', [\App\Enum\StatutPaiement::VALIDE->value, \App\Enum\StatutPaiement::EN_ATTENTE->value])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
