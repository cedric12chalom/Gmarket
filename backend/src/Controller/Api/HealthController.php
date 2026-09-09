<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/health')]
class HealthController extends AbstractController
{
    #[Route('', name: 'api_health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(['status' => 'ok', 'service' => 'gmarket-api']);
    }
}