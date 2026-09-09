<?php

namespace App\Controller\Api;

use App\Entity\Categorie;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CategorieController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('/categories', name: 'api_categories_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(Categorie::class)->findAll();
        return new JsonResponse(array_map(fn(Categorie $c) => EntitySerializer::categorie($c), $categories));
    }

    #[Route('/categories/{id}', name: 'api_categories_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $categorie = $em->getRepository(Categorie::class)->find($id);
        if (!$categorie instanceof Categorie) {
            return $this->error('Catégorie non trouvée.', 404);
        }
        return new JsonResponse(EntitySerializer::categorie($categorie));
    }

    #[Route('/categories', name: 'api_categories_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $data = json_decode($request->getContent(), true) ?? [];
            $categorie = new Categorie();
            $categorie->setNom($data['nom'] ?? '');
            $categorie->setTypeTransport($data['type_transport'] ?? 'standard');
            $categorie->setCommissionFixe(isset($data['commission_fixe']) ? (string) $data['commission_fixe'] : '0.00');
            $categorie->setCommissionPourcent(isset($data['commission_pourcent']) ? (string) $data['commission_pourcent'] : '0.00');
            $categorie->setApprouve($data['approuve'] ?? false);
            $em->persist($categorie);
            $em->flush();
            return new JsonResponse(EntitySerializer::categorie($categorie), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/admin/categories/{id}', name: 'api_admin_categories_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $categorie = $em->getRepository(Categorie::class)->find($id);
            if (!$categorie instanceof Categorie) {
                return $this->error('Catégorie non trouvée.', 404);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            if (isset($data['nom'])) {
                $categorie->setNom($data['nom']);
            }
            if (isset($data['commission_fixe'])) {
                $categorie->setCommissionFixe((string) $data['commission_fixe']);
            }
            if (isset($data['commission_pourcent'])) {
                $categorie->setCommissionPourcent((string) $data['commission_pourcent']);
            }
            if (array_key_exists('approuve', $data)) {
                $categorie->setApprouve((bool) $data['approuve']);
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::categorie($categorie));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
