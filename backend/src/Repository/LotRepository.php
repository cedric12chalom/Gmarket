<?php

namespace App\Repository;

use App\Entity\Lot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lot::class);
    }

    public function findAvailableByProducteur(int $producteurId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.producteur = :producteurId')
            ->setParameter('producteurId', $producteurId)
            ->getQuery()
            ->getResult();
    }

    public function findByQrCode(string $qrCode): ?Lot
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.qrCode = :qrCode')
            ->setParameter('qrCode', $qrCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDisponiblesWithProducteur(): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.producteur', 'p')
            ->addSelect('p')
            ->andWhere('l.statut = :statut')
            ->andWhere('l.quantiteDisponible > 0')
            ->andWhere('COALESCE(l.latitude, p.latitude) IS NOT NULL')
            ->andWhere('COALESCE(l.longitude, p.longitude) IS NOT NULL')
            ->setParameter('statut', \App\Enum\StatutLot::DISPONIBLE->value)
            ->getQuery()
            ->getResult();
    }

    public function findDisponiblesByDistance(float $lat, float $lng, float $rayonKm): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT l.*, p.id AS producteur_id, 
                   6371 * ACOS(COS(RADIANS(:lat)) * COS(RADIANS(COALESCE(l.latitude, p.latitude))) * COS(RADIANS(COALESCE(l.longitude, p.longitude)) - RADIANS(:lng)) + SIN(RADIANS(:lat)) * SIN(RADIANS(COALESCE(l.latitude, p.latitude)))) AS distance_km
            FROM lot l
            JOIN `user` p ON p.id = l.producteur_id
            WHERE l.statut = :statut
              AND l.quantite_disponible > 0
              AND COALESCE(l.latitude, p.latitude) IS NOT NULL
              AND COALESCE(l.longitude, p.longitude) IS NOT NULL
            HAVING distance_km <= :rayon_km
            ORDER BY distance_km ASC
        ";
        $stmt = $conn->executeQuery($sql, [
            'lat' => $lat,
            'lng' => $lng,
            'statut' => \App\Enum\StatutLot::DISPONIBLE->value,
            'rayon_km' => $rayonKm,
        ]);
        $rows = $stmt->fetchAllAssociative();
        $lots = [];
        foreach ($rows as $row) {
            $lot = $this->getEntityManager()->getRepository(Lot::class)->find($row['id']);
            if ($lot instanceof Lot) {
                $lot->distanceKm = (float) $row['distance_km'];
                $lots[] = $lot;
            }
        }
        return $lots;
    }
}
