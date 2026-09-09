<?php

namespace App\Controller\Api;

use App\Entity\Commande;
use App\Entity\Lot;
use App\Entity\Paiement;
use App\Entity\Produit;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\StatutCommande;
use App\Enum\StatutPaiement;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }
    #[Route('/dashboard/api/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function stats(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $revenue = (float) $em->createQueryBuilder()
                ->select('COALESCE(SUM(p.montant), 0)')
                ->from(Paiement::class, 'p')
                ->andWhere('p.statut = :statut')
                ->setParameter('statut', StatutPaiement::VALIDE->value)
                ->getQuery()
                ->getSingleScalarResult();
            $ordersCount = (int) $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(Commande::class, 'c')
                ->getQuery()
                ->getSingleScalarResult();
            $paidOrdersCount = (int) $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(Commande::class, 'c')
                ->andWhere('c.statut IN (:statuses)')
                ->setParameter('statuses', [StatutCommande::PAYEE->value, StatutCommande::EN_COURS->value, StatutCommande::LIVREE->value])
                ->getQuery()
                ->getSingleScalarResult();
            $usersCount = (int) $em->createQueryBuilder()
                ->select('COUNT(u.id)')
                ->from(User::class, 'u')
                ->getQuery()
                ->getSingleScalarResult();
            $lotsCount = (int) $em->createQueryBuilder()
                ->select('COUNT(l.id)')
                ->from(Lot::class, 'l')
                ->getQuery()
                ->getSingleScalarResult();
            $produitsCount = (int) $em->createQueryBuilder()
                ->select('COUNT(p.id)')
                ->from(Produit::class, 'p')
                ->getQuery()
                ->getSingleScalarResult();
            $allUsers = $em->getRepository(User::class)->findAll();
            $userRoles = [];
            foreach ($allUsers as $u) {
                $r = $u->getRole();
                $userRoles[$r] = ($userRoles[$r] ?? 0) + 1;
            }
            $categoryRows = $em->createQueryBuilder()
                ->select('cat.nom AS nom, COUNT(p.id) AS total')
                ->from(Produit::class, 'p')
                ->join('p.categorie', 'cat')
                ->groupBy('cat.id')
                ->getQuery()
                ->getResult();
            $categoryDistribution = [];
            foreach ($categoryRows as $row) {
                $categoryDistribution[$row['nom']] = (int) $row['total'];
            }
            return new JsonResponse([
                'revenus' => $revenue,
                'commandes' => $ordersCount,
                'commandes_payees' => $paidOrdersCount,
                'utilisateurs' => $usersCount,
                'lots' => $lotsCount,
                'produits' => $produitsCount,
                'repartition_roles' => $userRoles,
                'repartition_categories' => $categoryDistribution,
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/produits/{id}/prix', name: 'api_admin_produit_prix', methods: ['PUT'])]
    public function updateProductPrice(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $produit = $em->getRepository(Produit::class)->find($id);
            if (!$produit instanceof Produit) {
                return $this->error('Produit non trouvé.', 404);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            if (isset($data['prix_min'])) {
                $produit->setPrixMin((string) $data['prix_min']);
            }
            if (isset($data['prix_max'])) {
                $produit->setPrixMax((string) $data['prix_max']);
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::produit($produit));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/transactions', name: 'api_admin_transactions', methods: ['GET'])]
    public function transactions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $role = $request->query->get('role');
            $motif = $request->query->get('motif');
            $userId = $request->query->get('utilisateur_id') ? (int) $request->query->get('utilisateur_id') : null;
            $transactions = $em->getRepository(Transaction::class)->findAllFiltered($role, $motif, $userId);
            return new JsonResponse([
                'transactions' => array_map(fn(Transaction $t) => [
                    'id' => $t->getId(),
                    'utilisateur' => $t->getUtilisateur() ? ['id' => $t->getUtilisateur()->getId(), 'nom' => $t->getUtilisateur()->getNom(), 'prenom' => $t->getUtilisateur()->getPrenom(), 'role' => $t->getUtilisateur()->getRole()] : null,
                    'type' => $t->getType(),
                    'montant' => (float) $t->getMontant(),
                    'motif' => $t->getMotif(),
                    'reference' => $t->getReference(),
                    'solde_apres' => (float) $t->getSoldeApres(),
                    'date_creation' => $t->getDateCreation()?->format('c'),
                ], $transactions),
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
