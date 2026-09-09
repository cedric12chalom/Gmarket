<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Recherche par nom de boutique ou pseudo TikTok (utilisé par la recherche
     * vendeur et par la page boutique).
     */
    public function searchByBoutique(string $q): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.boutiques', 'b')
            ->where('LOWER(b.nom) LIKE :q OR LOWER(b.tiktokPseudo) LIKE :q')
            ->setParameter('q', '%' . strtolower($q) . '%')
            ->orderBy('b.nom', 'ASC');
        return $qb->getQuery()->getResult();
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u MEMBER OF :role')
            ->setParameter('role', [$role])
            ->getQuery()
            ->getResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}