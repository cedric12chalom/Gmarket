<?php

namespace App\Controller\Api;

use App\Entity\Categorie;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
class CategorieController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->entityManager->getRepository(Categorie::class)->findAll();
        return $this->json([
            'categories' => array_map(fn (Categorie $c) => [
                'id' => $c->getId(),
                'nom' => $c->getNom(),
                'slug' => $c->getSlug(),
            ], $categories),
        ]);
    }
}