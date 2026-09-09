<?php

namespace App\Controller\Api;

use App\Entity\Lot;
use App\Entity\Producteur;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\Variete;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/lots')]
class LotController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('', name: 'api_lots_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $search = $request->query->get('search');
        $categorieId = $request->query->get('categorie');
        $prixMin = $request->query->get('prix_min');
        $prixMax = $request->query->get('prix_max');
        $fraicheur = $request->query->get('fraicheur');
        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');
        $rayonKm = $request->query->get('rayon_km');
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 20);

        if ($lat !== null && $lng !== null && $rayonKm !== null) {
            $lots = $em->getRepository(Lot::class)->findDisponiblesByDistance((float) $lat, (float) $lng, (float) $rayonKm);
        } else {
            $lots = $em->getRepository(Lot::class)->findAll();
        }

        $filtered = [];
        foreach ($lots as $lot) {
            if ($lot->getStatut() === \App\Enum\StatutLot::EXPIRE->value || $lot->getStatut() === \App\Enum\StatutLot::RETIRE->value || $lot->getQuantiteRestante() <= 0) {
                continue;
            }
            if ($search && stripos($lot->getProduit()?->getNom() ?? '', $search) === false) {
                continue;
            }
            if ($categorieId && (string) $lot->getProduit()?->getCategorie()?->getId() !== (string) $categorieId) {
                continue;
            }
            $prix = (float) $lot->getPrixProducteur();
            if ($prixMin !== null && $prixMin !== '' && $prix < (float) $prixMin) {
                continue;
            }
            if ($prixMax !== null && $prixMax !== '' && $prix > (float) $prixMax) {
                continue;
            }
            if ($fraicheur && $lot->calculerIndiceFraicheur() !== $fraicheur) {
                continue;
            }
            $filtered[] = EntitySerializer::lot($lot);
        }
        $total = count($filtered);
        $offset = ($page - 1) * $perPage;
        return new JsonResponse(array_slice($filtered, $offset, $perPage));
    }

    #[Route('/producteur/{userId}', name: 'api_lots_by_producteur', methods: ['GET'])]
    public function byProducteur(int $userId, EntityManagerInterface $em): JsonResponse
    {
        // Try to find a user first. If the user does not exist, return 404.
        $user = $em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user instanceof \App\Entity\User) {
            return $this->error('Utilisateur non trouvé.', 404);
        }

        // If the user exists but is not a Producteur, return an empty array (no lots).
        if (!$user instanceof Producteur) {
            return new JsonResponse([]);
        }

        $lots = $em->getRepository(Lot::class)->findAvailableByProducteur($user->getId());
        return new JsonResponse(array_map(fn(Lot $lot) => EntitySerializer::lot($lot), $lots));
    }

    #[Route('/{id}', name: 'api_lots_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $lot = $em->getRepository(Lot::class)->find($id);
        if (!$lot instanceof Lot) {
            return $this->error('Lot non trouvé.', 404);
        }
        return new JsonResponse(EntitySerializer::lot($lot));
    }

    #[Route('/qr/{qrCode}', name: 'api_lots_qr', methods: ['GET'])]
    public function qr(string $qrCode, EntityManagerInterface $em): JsonResponse
    {
        $lot = $em->getRepository(Lot::class)->findByQrCode($qrCode);
        if (!$lot instanceof Lot) {
            return $this->error('QR Code non reconnu.', 404);
        }
        $produit = $lot->getProduit();
        $producteur = $lot->getProducteur();
        return new JsonResponse([
            'qr_code' => $lot->getQrCode(),
            'produit' => [
                'nom' => $produit?->getNom(),
                'description' => $produit?->getDescription(),
                'unite' => $produit?->getUnite(),
                'categorie' => $produit?->getCategorie() ? ['nom' => $produit->getCategorie()->getNom(), 'type_transport' => $produit->getCategorie()->getTypeTransport()] : null,
            ],
            'producteur' => [
                'prenom' => $producteur?->getPrenom(),
                'nom' => $producteur?->getNom(),
                'localisation' => $producteur?->getLocalisation(),
            ],
            'date_recolte' => $lot->getDateRecolte()?->format('Y-m-d'),
            'indice_fraicheur' => $lot->calculerIndiceFraicheur(),
            'prix_producteur' => (float) $lot->getPrixProducteur(),
            'quantite_disponible' => $lot->getQuantiteRestante(),
        ]);
    }

    #[Route('', name: 'api_lots_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'producteur');
            if (!$user instanceof Producteur) {
                return $this->error('Rôle producteur requis.', 403);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $produit = isset($data['produit_id']) ? $em->getRepository(Produit::class)->find($data['produit_id']) : null;
            if (!$produit instanceof Produit) {
                return $this->error('Produit non trouvé.', 404);
            }
            if (!$produit->getCategorie()?->isApprouve()) {
                return $this->error('RG18 : la catégorie doit être approuvée par un administrateur.', 400);
            }
            if (!$produit->validerPrix((float) ($data['prix_producteur'] ?? -1))) {
                return $this->error('RG01 : prix producteur hors de la fourchette autorisée.', 400);
            }
            if (empty($data['date_recolte']) || empty($data['duree_conservation'])) {
                return $this->error('RG09 : date de récolte et durée de conservation obligatoires.', 400);
            }
            $lot = new Lot();
            $lot->setProduit($produit);
            $lot->setProducteur($user);
            $lot->setQuantiteDisponible((string) ($data['quantite_disponible'] ?? 0));
            $lot->setPrixProducteur((string) ($data['prix_producteur'] ?? 0));
            $lot->setDateRecolte(new \DateTime($data['date_recolte']));
            $lot->setDureeConservation((int) $data['duree_conservation']);
            $variete = $this->resolveVariete($data, $produit, $em);
            if ($variete instanceof JsonResponse) {
                return $variete;
            }
            $lot->setVariete($variete);
            $lot->setLieuProduction($data['lieu_production'] ?? $user->getLocalisation());
            $lot->setLatitude($this->resolveLotLatitude($data, $user));
            $lot->setLongitude($this->resolveLotLongitude($data, $user));
            $em->persist($lot);
            $em->flush();
            $lot->setQrCode($lot->genererQRCode());
            $em->flush();
            return new JsonResponse(EntitySerializer::lot($lot), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}', name: 'api_lots_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $lot = $em->getRepository(Lot::class)->find($id);
            if (!$lot instanceof Lot) {
                return $this->error('Lot non trouvé.', 404);
            }
            if ($lot->getProducteur()?->getId() !== $user->getId() && $user->getRole() !== 'admin') {
                return $this->error('Accès refusé.', 403);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            if (isset($data['prix_producteur'])) {
                if (!$lot->getProduit()?->validerPrix((float) $data['prix_producteur'])) {
                    return $this->error('RG01 : prix producteur hors de la fourchette autorisée.', 400);
                }
                $lot->setPrixProducteur((string) $data['prix_producteur']);
            }
            if (isset($data['quantite_disponible'])) {
                $lot->setQuantiteDisponible((string) $data['quantite_disponible']);
            }
            if (array_key_exists('variete_id', $data) || array_key_exists('variete', $data)) {
                $produit = $lot->getProduit();
                if (!$produit instanceof Produit) {
                    return $this->error('Produit non trouvé pour ce lot.', 400);
                }
                $variete = $this->resolveVariete($data, $produit, $em);
                if ($variete instanceof JsonResponse) {
                    return $variete;
                }
                $lot->setVariete($variete);
            }
            if (isset($data['latitude'])) {
                $lot->setLatitude($data['latitude'] !== '' && $data['latitude'] !== null ? (string) $data['latitude'] : null);
            }
            if (isset($data['longitude'])) {
                $lot->setLongitude($data['longitude'] !== '' && $data['longitude'] !== null ? (string) $data['longitude'] : null);
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::lot($lot));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}', name: 'api_lots_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $lot = $em->getRepository(Lot::class)->find($id);
            if (!$lot instanceof Lot) {
                return $this->error('Lot non trouvé.', 404);
            }
            if ($lot->getProducteur()?->getId() !== $user->getId() && $user->getRole() !== 'admin') {
                return $this->error('Accès refusé.', 403);
            }
            $em->remove($lot);
            $em->flush();
            return new JsonResponse(['message' => 'Lot supprimé.']);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    private function resolveVariete(array $data, Produit $produit, EntityManagerInterface $em): Variete|JsonResponse|null
    {
        if (isset($data['variete_id']) && $data['variete_id'] !== '' && $data['variete_id'] !== null) {
            $variete = $em->getRepository(Variete::class)->find((int) $data['variete_id']);
            if (!$variete instanceof Variete || $variete->getProduit()?->getId() !== $produit->getId()) {
                return $this->error('Variété introuvable pour ce produit.', 400);
            }
            return $variete;
        }

        if (array_key_exists('variete', $data) && is_string($data['variete']) && trim($data['variete']) !== '') {
            $variete = $em->getRepository(Variete::class)->findOneBy([
                'produit' => $produit->getId(),
                'nom' => trim($data['variete']),
            ]);
            if (!$variete instanceof Variete) {
                return $this->error('Variété introuvable pour ce produit.', 400);
            }
            return $variete;
        }

        return null;
    }

    private function resolveLotLatitude(array $data, Producteur $producteur): ?string
    {
        $lat = $data['latitude'] ?? $producteur->getLatitude();
        return $lat !== null && $lat !== '' ? (string) $lat : null;
    }

    private function resolveLotLongitude(array $data, Producteur $producteur): ?string
    {
        $lng = $data['longitude'] ?? $producteur->getLongitude();
        return $lng !== null && $lng !== '' ? (string) $lng : null;
    }
}
