<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $notifications = $em->getRepository(Notification::class)->findBy(['utilisateur' => $user->getId()], ['dateEnvoi' => 'DESC']);
            return new JsonResponse(array_map(fn(Notification $n) => EntitySerializer::notification($n), $notifications));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/lu', name: 'api_notifications_mark_read', methods: ['PUT'])]
    public function markRead(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $notification = $em->getRepository(Notification::class)->find($id);
        if (!$notification instanceof Notification) {
            return $this->error('Notification non trouvée.', 404);
        }
        if ($notification->getUtilisateur()?->getId() !== $user->getId()) {
            return $this->error('Accès refusé.', 403);
        }
        $notification->marquerCommeLu();
        $em->flush();
        return new JsonResponse(EntitySerializer::notification($notification));
    }

    #[Route('/lu', name: 'api_notifications_mark_all_read', methods: ['PUT'])]
    public function markAllRead(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $notifications = $em->getRepository(Notification::class)->findNonLuesByUtilisateur($user->getId());
        foreach ($notifications as $notification) {
            $notification->marquerCommeLu();
        }
        $em->flush();
        return new JsonResponse(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}
