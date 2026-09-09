<?php

namespace App\Controller\Api;

use App\Service\AiAssistantServiceInterface;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/assistant')]
class AssistantController extends AbstractController
{
    use TokenAwareControllerTrait;

    public function __construct(
        private TokenService $tokenService,
        private AiAssistantServiceInterface $aiAssistantService,
    ) {}

    #[Route('/message', name: 'api_assistant_message', methods: ['POST'])]
    public function message(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $question = trim((string) ($data['question'] ?? ''));
            if ($question === '') {
                return $this->error('La question est requise.', 400);
            }
            $reponse = $this->aiAssistantService->answer($question);
            return new JsonResponse(['question' => $question, 'reponse' => $reponse]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}