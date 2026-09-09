<?php

namespace App\Controller\Api;

use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Entity\Lot;
use App\Entity\Producteur;
use App\Entity\User;
use App\Enum\StatutLot;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/carte')]
class CarteController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('/marqueurs', name: 'api_carte_marqueurs', methods: ['GET'])]
    public function marqueurs(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));

            $roleFilter = $request->query->get('role');
            $repo = $em->getRepository(User::class);

            $producteurs = [];
            $lots = [];
            $livreurs = [];

            if ($roleFilter === null || $roleFilter === 'producteur') {
                $rawProducteurs = $repo->findByRoleWithCoordinates('producteur');
                $producteurs = array_values(array_filter($rawProducteurs, fn(User $u) => $u instanceof Producteur));
                $lots = $em->getRepository(Lot::class)->findDisponiblesWithProducteur();
            }

            if ($roleFilter === null || $roleFilter === 'livreur') {
                $rawLivreurs = $repo->findByRoleWithCoordinates('livreur');
                $livreurs = array_values(array_filter($rawLivreurs, fn(User $u) => $u instanceof Livreur && $u->isDisponible() && $u->isActif()));
            }

            return new JsonResponse([
                'producteurs' => array_map(fn(Producteur $p) => [
                    'id' => $p->getId(),
                    'nom' => $p->getNom(),
                    'prenom' => $p->getPrenom(),
                    'localisation' => $p->getLocalisation(),
                    'latitude' => $p->getLatitude() !== null ? (float) $p->getLatitude() : null,
                    'longitude' => $p->getLongitude() !== null ? (float) $p->getLongitude() : null,
                ], $producteurs),
                'lots' => array_map(fn(Lot $l) => [
                    'id' => $l->getId(),
                    'produit' => [
                        'id' => $l->getProduit()?->getId(),
                        'nom' => $l->getProduit()?->getNom(),
                        'unite' => $l->getProduit()?->getUnite(),
                    ],
                    'producteur_id' => $l->getProducteur()?->getId(),
                    'prix_producteur' => (float) $l->getPrixProducteur(),
                    'quantite_disponible' => (float) $l->getQuantiteDisponible(),
                    'latitude' => $l->getLatitude() ?? $l->getProducteur()?->getLatitude(),
                    'longitude' => $l->getLongitude() ?? $l->getProducteur()?->getLongitude(),
                ], $lots),
                'livreurs' => array_map(fn(Livreur $l) => [
                    'id' => $l->getId(),
                    'nom' => $l->getNom(),
                    'prenom' => $l->getPrenom(),
                    'type_transport' => $l->getTypeTransport(),
                    'latitude' => $l->getLatitude() !== null ? (float) $l->getLatitude() : null,
                    'longitude' => $l->getLongitude() !== null ? (float) $l->getLongitude() : null,
                ], $livreurs),
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}