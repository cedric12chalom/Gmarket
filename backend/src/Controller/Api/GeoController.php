<?php

namespace App\Controller\Api;

use App\Entity\GeoTrack;
use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Entity\Lot;
use App\Entity\Producteur;
use App\Entity\User;
use App\Enum\StatutLivraison;
use App\Service\EntitySerializer;
use App\Service\GeoMatcherService;
use App\Service\LivraisonGeoService;
use App\Service\RoutingService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/geo')]
class GeoController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;
    private RoutingService $routingService;
    private GeoMatcherService $geoMatcherService;
    private LivraisonGeoService $livraisonGeoService;

    public function __construct(
        TokenService $tokenService,
        RoutingService $routingService,
        GeoMatcherService $geoMatcherService,
        LivraisonGeoService $livraisonGeoService
    ) {
        $this->tokenService = $tokenService;
        $this->routingService = $routingService;
        $this->geoMatcherService = $geoMatcherService;
        $this->livraisonGeoService = $livraisonGeoService;
    }

    #[Route('/track', name: 'api_geo_track', methods: ['POST'])]
    public function track(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $lat = (float) ($data['latitude'] ?? 0);
            $lng = (float) ($data['longitude'] ?? 0);
            if ($lat === 0.0 && $lng === 0.0) {
                return $this->error('Latitude et longitude invalides.', 400);
            }

            $type = $data['type'] ?? 'delivery_person';
            if ($type === 'delivery_person' && $user instanceof Livreur && !$user->estEligibleAuxMissions()) {
                return $this->error('Votre dossier de livraison doit être validé avant de partager votre position.', 403);
            }
            $refId = isset($data['reference_id']) ? (int) $data['reference_id'] : $user->getId();
            $emoji = $data['emoji'] ?? null;

            $repo = $em->getRepository(GeoTrack::class);
            $existing = $repo->findOneBy(['userId' => $user->getId(), 'type' => $type]);
            if ($existing instanceof GeoTrack) {
                $existing->setLatitude((string) $lat)
                    ->setLongitude((string) $lng)
                    ->setUpdatedAt(new \DateTime())
                    ->setReferenceId($refId);
                if ($emoji !== null) {
                    $existing->setEmoji($emoji);
                }
            } else {
                $track = new GeoTrack();
                $track->setUserId($user->getId())
                    ->setReferenceId($refId)
                    ->setType($type)
                    ->setLatitude((string) $lat)
                    ->setLongitude((string) $lng)
                    ->setUpdatedAt(new \DateTime())
                    ->setEmoji($emoji);
                $em->persist($track);
            }
            $em->flush();

            $user->setLatitude((string) $lat)->setLongitude((string) $lng);
            $em->flush();

            return new JsonResponse(['message' => 'Position enregistrée.']);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/positions', name: 'api_geo_positions', methods: ['GET'])]
    public function positions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $type = $request->query->get('type');
            $repo = $em->getRepository(GeoTrack::class);
            $since = new \DateTime('-30 minutes');

            $qb = $repo->createQueryBuilder('g')
                ->andWhere('g.updatedAt > :since')
                ->setParameter('since', $since);

            if ($type) {
                $qb->andWhere('g.type = :type')->setParameter('type', $type);
            }

            $positions = $qb->getQuery()->getResult();

            return new JsonResponse(array_map(fn(GeoTrack $g) => [
                'id' => $g->getId(),
                'user_id' => $g->getUserId(),
                'reference_id' => $g->getReferenceId(),
                'latitude' => $g->getLatitude() !== null ? (float) $g->getLatitude() : null,
                'longitude' => $g->getLongitude() !== null ? (float) $g->getLongitude() : null,
                'type' => $g->getType(),
                'updated_at' => $g->getUpdatedAt()?->format('c'),
                'emoji' => $g->getEmoji(),
                'estimated_distance' => $g->getEstimatedDistance() !== null ? (float) $g->getEstimatedDistance() : null,
                'estimated_duration' => $g->getEstimatedDuration() !== null ? (float) $g->getEstimatedDuration() : null,
            ], $positions));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/productions', name: 'api_geo_productions', methods: ['GET'])]
    public function productions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $producteurs = $em->getRepository(User::class)->findByRoleWithCoordinates('producteur');
            $producteursData = array_map(fn(Producteur $p) => [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prenom' => $p->getPrenom(),
                'localisation' => $p->getLocalisation(),
                'latitude' => $p->getLatitude() !== null ? (float) $p->getLatitude() : null,
                'longitude' => $p->getLongitude() !== null ? (float) $p->getLongitude() : null,
                'emoji' => '🧑‍🌾',
            ], array_values(array_filter($producteurs, fn(User $u) => $u instanceof Producteur)));

            $lots = $em->getRepository(Lot::class)->findDisponiblesWithProducteur();
            $lotsData = array_map(fn(Lot $l) => [
                'id' => $l->getId(),
                'produit' => [
                    'id' => $l->getProduit()?->getId(),
                    'nom' => $l->getProduit()?->getNom(),
                    'unite' => $l->getProduit()?->getUnite(),
                    'categorie' => $l->getProduit()?->getCategorie()?->getNom(),
                ],
                'producteur_id' => $l->getProducteur()?->getId(),
                'prix_producteur' => (float) $l->getPrixProducteur(),
                'quantite_disponible' => (float) $l->getQuantiteDisponible(),
                'date_recolte' => $l->getDateRecolte()?->format('Y-m-d'),
                'indice_fraicheur' => $l->calculerIndiceFraicheur(),
                'latitude' => $l->getLatitude() ?? $l->getProducteur()?->getLatitude(),
                'longitude' => $l->getLongitude() ?? $l->getProducteur()?->getLongitude(),
                'emoji' => $this->resolveProduitEmoji($l->getProduit()?->getNom() ?? ''),
            ], $lots);

            return new JsonResponse([
                'producteurs' => $producteursData,
                'lots' => $lotsData,
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/deliveries', name: 'api_geo_deliveries', methods: ['GET'])]
    public function deliveries(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $repo = $em->getRepository(Livraison::class);
            $all = $repo->findAll();
            $active = array_values(array_filter($all, fn(Livraison $l) => $l->getStatut() !== StatutLivraison::LIVREE->value));

            $routes = $this->buildDeliveryRoutes($active, $em);
            $deliveriesData = [];
            foreach ($active as $l) {
                $route = $routes[$l->getId()] ?? null;
                $deliveriesData[] = [
                    'id' => $l->getId(),
                    'livraison' => EntitySerializer::livraison($l),
                    'route_geojson' => $route['geometry'] ?? null,
                    'route_distance' => $route['distance'] ?? null,
                    'route_duration' => $route['duration'] ?? null,
                    'route_waypoints' => isset($route['waypoints'])
                        ? array_map(fn(array $w) => ['latitude' => $w['lat'], 'longitude' => $w['lng']], $route['waypoints'])
                        : null,
                ];
            }

            return new JsonResponse($deliveriesData);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/match/{id}', name: 'api_geo_match', methods: ['POST'])]
    public function match(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            if (!$this->canManageLivraison($livraison, $user)) {
                return $this->error('Accès refusé.', 403);
            }

            $match = $this->geoMatcherService->findBestMatchForDelivery($livraison);
            if ($match === null) {
                return $this->error('Aucun livreur disponible trouvé.', 404);
            }

            $livreur = $match['livreur'];
            return new JsonResponse([
                'livreur' => EntitySerializer::user($livreur),
                'distance' => $match['distance'],
                'duration' => $match['duration'],
                'geometry' => $match['geometry'],
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/auto-assign/{id}', name: 'api_geo_auto_assign', methods: ['POST'])]
    public function autoAssign(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            if (!$this->canManageLivraison($livraison, $user)) {
                return $this->error('Accès refusé.', 403);
            }

            $match = $this->geoMatcherService->findBestMatchForDelivery($livraison);
            if ($match === null) {
                return $this->error('Aucun livreur disponible trouvé.', 404);
            }

            $livreur = $match['livreur'];
            if (!$livreur instanceof Livreur) {
                return $this->error('Livreur invalide.', 400);
            }
            if (!$livreur->isDisponible() || !$livreur->isActif()) {
                return $this->error('Le livreur n\'est pas disponible.', 400);
            }

            // RG : rayon max 100km
            if ($match['distance'] > 100000) {
                return $this->error('Aucun livreur dans un rayon de 100 km.', 400);
            }

            $livraison->assignerParAcheteur($livreur);
            $em->flush();

            return new JsonResponse([
                'message' => 'Livreur attribué automatiquement.',
                'livreur' => EntitySerializer::user($livreur),
                'distance' => $match['distance'],
                'duration' => $match['duration'],
                'geometry' => $match['geometry'],
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/route/{id}', name: 'api_geo_route', methods: ['GET'])]
    public function route(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            if (!$this->canManageLivraison($livraison, $user)) {
                return $this->error('Accès refusé.', 403);
            }

            $route = $this->buildDeliveryRouteGeometry($livraison, $em);
            if ($route === null) {
                return $this->error('Impossible de calculer la route : coordonnées manquantes.', 400);
            }
            return new JsonResponse([
                'distance' => $route['distance'],
                'duration' => $route['duration'],
                'geometry' => $route['geometry'],
                'waypoints' => array_map(fn(array $w) => ['latitude' => $w['lat'], 'longitude' => $w['lng']], $route['waypoints']),
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/delivery-person/{id}/position', name: 'api_geo_delivery_person_position', methods: ['GET'])]
    public function deliveryPersonPosition(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livreur = $em->getRepository(Livreur::class)->find($id);
            if (!$livreur instanceof Livreur) {
                return $this->error('Livreur non trouvé.', 404);
            }

            $track = $em->getRepository(GeoTrack::class)->findByReference($id, 'delivery_person');
            if ($track instanceof GeoTrack) {
                return new JsonResponse([
                    'user_id' => $track->getUserId(),
                    'reference_id' => $track->getReferenceId(),
                    'latitude' => $track->getLatitude() !== null ? (float) $track->getLatitude() : null,
                    'longitude' => $track->getLongitude() !== null ? (float) $track->getLongitude() : null,
                    'updated_at' => $track->getUpdatedAt()?->format('c'),
                ]);
            }

            return new JsonResponse([
                'user_id' => $livreur->getId(),
                'reference_id' => $livreur->getId(),
                'latitude' => $livreur->getLatitude() !== null ? (float) $livreur->getLatitude() : null,
                'longitude' => $livreur->getLongitude() !== null ? (float) $livreur->getLongitude() : null,
                'updated_at' => null,
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    private function canManageLivraison(Livraison $livraison, User $user): bool
    {
        if ($user->getRole() === 'admin') {
            return true;
        }
        $commande = $livraison->getCommande();
        if ($commande instanceof \App\Entity\Commande) {
            if ($commande->getAcheteur()?->getId() === $user->getId()) {
                return true;
            }
            foreach ($commande->getLignes() as $ligne) {
                if ($ligne->getLot()?->getProducteur()?->getId() === $user->getId()) {
                    return true;
                }
            }
        }
        return $livraison->getLivreur()?->getId() === $user->getId();
    }

    private function buildDeliveryRoutes(array $livraisons, EntityManagerInterface $em): array
    {
        $waypointSets = [];
        foreach ($livraisons as $l) {
            $waypoints = $this->buildDeliveryWaypoints($l, $em);
            if ($waypoints !== null) {
                $waypointSets[$l->getId()] = $waypoints;
            }
        }

        $routes = $this->routingService->getRouteMultiBatch($waypointSets);
        $result = [];
        foreach ($waypointSets as $id => $waypoints) {
            $result[$id] = ($routes[$id] ?? null) !== null
                ? $routes[$id] + ['waypoints' => $waypoints]
                : null;
        }
        return $result;
    }

    private function buildDeliveryRouteGeometry(?Livraison $livraison, EntityManagerInterface $em): ?array
    {
        $waypoints = $this->buildDeliveryWaypoints($livraison, $em);
        if ($waypoints === null) {
            return null;
        }

        try {
            $route = $this->routingService->getRouteMulti($waypoints);
            $route['waypoints'] = $waypoints;
            return $route;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildDeliveryWaypoints(?Livraison $livraison, EntityManagerInterface $em): ?array
    {
        if (!$livraison instanceof Livraison) {
            return null;
        }
        $commande = $livraison->getCommande();
        if (!$commande instanceof \App\Entity\Commande) {
            return null;
        }

        $waypoints = [];

        $livreur = $livraison->getLivreur();
        if ($livreur instanceof Livreur) {
            $track = $em->getRepository(GeoTrack::class)->findByReference($livreur->getId(), 'delivery_person');
            if ($track instanceof GeoTrack && $track->getLatitude() && $track->getLongitude()) {
                $waypoints[] = ['lat' => (float) $track->getLatitude(), 'lng' => (float) $track->getLongitude()];
            } elseif ($livreur->getLatitude() && $livreur->getLongitude()) {
                $waypoints[] = ['lat' => (float) $livreur->getLatitude(), 'lng' => (float) $livreur->getLongitude()];
            }
        }

        $origine = $this->livraisonGeoService->pointOrigine($commande->getLignes());
        if ($origine !== null) {
            $waypoints[] = $origine;
        }

        $destination = $this->livraisonGeoService->pointDestination($commande->getAcheteur());
        if ($destination !== null) {
            $waypoints[] = $destination;
        }

        if (count($waypoints) < 2) {
            return null;
        }

        return $waypoints;
    }

    private function resolveProduitEmoji(string $nom): string
    {
        $map = [
            ['keywords' => ['fruits', 'pomme', 'mangue', 'banane', 'ananas', 'raisin', 'pastèque', 'melon'], 'emoji' => '🍎'],
            ['keywords' => ['légumes', 'carotte', 'tomate', 'concombre', 'poivron', 'salade'], 'emoji' => '🥕'],
            ['keywords' => ['céréales', 'maïs', 'riz', 'millet', 'sorgho', 'blé'], 'emoji' => '🌾'],
            ['keywords' => ['viande', 'bœuf', 'porc', 'poulet', 'agneau', 'mouton'], 'emoji' => '🥩'],
            ['keywords' => ['lait', 'laitier', 'fromage', 'yaourt', 'beurre'], 'emoji' => '🥛'],
            ['keywords' => ['miel', 'nectar'], 'emoji' => '🍯'],
            ['keywords' => ['vin', 'bière'], 'emoji' => '🍷'],
            ['keywords' => ['épices', 'piment', 'gingembre', 'curcuma'], 'emoji' => '🌶️'],
            ['keywords' => ['fleur', 'hibiscus', 'rose'], 'emoji' => '🌻'],
            ['keywords' => ['plante', 'herbe', 'moringa', 'neem'], 'emoji' => '🌿'],
        ];
        $normalized = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom));
        foreach ($map as $entry) {
            foreach ($entry['keywords'] as $kw) {
                if (str_contains($normalized, $kw)) {
                    return $entry['emoji'];
                }
            }
        }
        return '🌱';
    }
}

