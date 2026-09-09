<?php

namespace App\Repository;

use App\Entity\GeoTrack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GeoTrackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeoTrack::class);
    }

    public function findActiveByType(string $type): array
    {
        $since = new \DateTime('-30 minutes');
        return $this->createQueryBuilder('g')
            ->andWhere('g.type = :type')
            ->andWhere('g.updatedAt > :since')
            ->setParameter('type', $type)
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }

    public function findNearby(float $lat, float $lng, float $radiusKm, ?string $type = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $since = (new \DateTime('-30 minutes'))->format('Y-m-d H:i:s');

        $whereParts = ['g.updated_at > :since'];
        $params = [
            'lat' => $lat,
            'lng' => $lng,
            'since' => $since,
            'radius' => $radiusKm,
        ];

        if ($type !== null) {
            $whereParts[] = 'g.type = :type';
            $params['type'] = $type;
        }

        $whereSql = implode(' AND ', $whereParts);

        $sql = "
            SELECT g.*,
                (6371 * ACOS(
                    COS(RADIANS(:lat)) * COS(RADIANS(g.latitude)) * COS(RADIANS(g.longitude) - RADIANS(:lng)) +
                    SIN(RADIANS(:lat)) * SIN(RADIANS(g.latitude))
                )) AS distance
            FROM geo_track g
            WHERE {$whereSql}
              AND (
                  6371 * ACOS(
                      COS(RADIANS(:lat)) * COS(RADIANS(g.latitude)) * COS(RADIANS(g.longitude) - RADIANS(:lng)) +
                      SIN(RADIANS(:lat)) * SIN(RADIANS(g.latitude))
                  )
              ) <= :radius
            ORDER BY distance ASC
            LIMIT 50
        ";

        $stmt = $conn->executeQuery($sql, $params);
        $rows = $stmt->fetchAllAssociative();

        $tracks = [];
        foreach ($rows as $row) {
            $track = $this->find((int) $row['id']);
            if ($track instanceof GeoTrack) {
                $tracks[] = $track;
            }
        }
        return $tracks;
    }

    public function findByReference(int $refId, string $type): ?GeoTrack
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.referenceId = :refId')
            ->andWhere('g.type = :type')
            ->setParameter('refId', $refId)
            ->setParameter('type', $type)
            ->orderBy('g.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
