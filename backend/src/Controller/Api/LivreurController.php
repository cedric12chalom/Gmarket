<?php

namespace App\Controller\Api;

use App\Entity\Acheteur;
use App\Entity\Commande;
use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/livreurs')]
class LivreurController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Liste les livreurs éligibles pour une livraison donnée.
     * Peut être appelé avec ?type_transport=... directement, ou avec ?commande_id=...
     * pour déterminer le type de transport requis via RG15.
     */
    #[Route('/disponibles', name: 'api_livreurs_disponibles', methods: ['GET'])]
    public function disponibles(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        if ($user->getRole() !== 'acheteur' && $user->getRole() !== 'admin') {
            return $this->error('Accès refusé.', 403);
        }

        $typeTransport = $this->resolveTypeTransport($request, $em, $user);
        if ($typeTransport === null) {
            return $this->error('Type de transport requis introuvable.', 400);
        }

        $livreurs = $em->getRepository(Livreur::class)->findEligible($typeTransport);
        return new JsonResponse(array_map(fn(Livreur $l) => EntitySerializer::user($l), $livreurs));
    }

    private function resolveTypeTransport(Request $request, EntityManagerInterface $em, \App\Entity\User $user): ?string
    {
        $typeTransportParam = $request->query->get('type_transport');
        if (is_string($typeTransportParam) && $typeTransportParam !== '') {
            return $typeTransportParam;
        }

        $commandeId = $request->query->get('commande_id');
        if ($commandeId !== null) {
            $commande = $em->getRepository(Commande::class)->find((int) $commandeId);
            if ($commande instanceof Commande && $commande->getAcheteur()?->getId() === $user->getId()) {
                foreach ($commande->getLignes() as $ligne) {
                    $type = $ligne->getLot()?->getProduit()?->getCategorie()?->getTypeTransport();
                    if ($type !== null) {
                        return $type;
                    }
                }
            }
        }

        return null;
    }
}
