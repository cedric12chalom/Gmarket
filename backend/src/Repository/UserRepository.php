<?php

namespace App\Repository;

use App\Entity\Acheteur;
use App\Entity\Administrateur;
use App\Entity\Livreur;
use App\Entity\Producteur;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByRole(string $role): array
    {
        $class = $this->roleToClass($role);
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF ' . $class)
            ->getQuery()
            ->getResult();
    }

    public function findByRoleWithCoordinates(string $role): array
    {
        $class = $this->roleToClass($role);
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF ' . $class)
            ->andWhere('u.latitude IS NOT NULL')
            ->andWhere('u.longitude IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    private function roleToClass(string $role): string
    {
        return match ($role) {
            'producteur' => Producteur::class,
            'acheteur' => Acheteur::class,
            'livreur' => Livreur::class,
            'admin' => Administrateur::class,
            default => User::class,
        };
    }
}
