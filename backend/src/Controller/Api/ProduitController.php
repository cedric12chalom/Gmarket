<?php

namespace App\Controller\Api;

use App\Entity\Categorie;
use App\Entity\HistoriquePrix;
use App\Entity\Produit;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/produits')]
class ProduitController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('', name: 'api_produits_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em, Request $request): JsonResponse
    {
        $produits = $em->getRepository(Produit::class)->findAllWithRelations();
        $result = array_map(fn(Produit $p) => EntitySerializer::produit($p), $produits);

        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');
        $rayonKm = $request->query->get('rayon_km');

        if ($lat !== null && $lng !== null && $rayonKm !== null) {
            $nearbyLotIds = [];
            $nearbyLots = $em->getRepository(Lot::class)->findDisponiblesByDistance((float) $lat, (float) $lng, (float) $rayonKm);
            foreach ($nearbyLots as $lot) {
                $pid = $lot->getProduit()?->getId();
                if ($pid !== null) {
                    $nearbyLotIds[$pid] = ($nearbyLotIds[$pid] ?? 0) + 1;
                }
            }
            $result = array_values(array_filter($result, fn(array $p) => isset($nearbyLotIds[$p['id']])));
        }

        return new JsonResponse($result);
    }

    #[Route('/{id}', name: 'api_produits_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $produit = $em->getRepository(Produit::class)->find($id);
        if (!$produit instanceof Produit) {
            return new JsonResponse(['message' => 'Produit non trouvé.', 'code' => 404], 404);
        }
        return new JsonResponse(EntitySerializer::produit($produit));
    }

    #[Route('/{id}/lots', name: 'api_produits_lots', methods: ['GET'])]
    public function lots(int $id, EntityManagerInterface $em): JsonResponse
    {
        $produit = $em->getRepository(Produit::class)->find($id);
        if (!$produit instanceof Produit) {
            return new JsonResponse(['message' => 'Produit non trouvé.', 'code' => 404], 404);
        }
        $lots = $produit->getLots();
        $available = [];
        foreach ($lots as $lot) {
            if ($lot->getStatut() !== \App\Enum\StatutLot::EXPIRE->value && $lot->getQuantiteRestante() > 0) {
                $available[] = EntitySerializer::lot($lot);
            }
        }
        return new JsonResponse($available);
    }

    #[Route('/{id}/historique-prix', name: 'api_produits_historique_prix', methods: ['GET'])]
    public function historiquePrix(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $produit = $em->getRepository(Produit::class)->find($id);
        if (!$produit instanceof Produit) {
            return new JsonResponse(['message' => 'Produit non trouvé.', 'code' => 404], 404);
        }
        $periode = (int) $request->query->get('periode', 30);
        $history = $em->getRepository(HistoriquePrix::class)->findByProduit($produit->getId(), $periode);
        return new JsonResponse([
            'produit_id' => $produit->getId(),
            'points' => array_map(fn(HistoriquePrix $h) => [
                'date' => $h->getDateReleve()?->format('Y-m-d'),
                'prix_moyen' => (float) $h->getPrixMoyen(),
            ], $history),
        ]);
    }

    #[Route('', name: 'api_produits_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $data = json_decode($request->getContent(), true) ?? [];

            foreach (['nom', 'unite', 'prix_min', 'prix_max', 'categorie_id'] as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    return $this->error("Champ requis manquant : {$field}.", 400);
                }
            }

            $categorie = $em->getRepository(Categorie::class)->find($data['categorie_id']);
            if (!$categorie instanceof Categorie) {
                return $this->error('Catégorie introuvable.', 404);
            }

            $produit = new Produit();
            $produit->setNom($data['nom']);
            $produit->setDescription($data['description'] ?? null);
            $produit->setUnite($data['unite']);
            $produit->setPrixMin((string) $data['prix_min']);
            $produit->setPrixMax((string) $data['prix_max']);
            $produit->setPhoto($data['photo'] ?? null);
            $produit->setCategorie($categorie);

            $em->persist($produit);
            $em->flush();

            return new JsonResponse(EntitySerializer::produit($produit), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
