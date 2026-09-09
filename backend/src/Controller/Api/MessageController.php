<?php

namespace App\Controller\Api;

use App\Entity\Livraison;
use App\Entity\Message;
use App\Entity\User;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/livraisons')]
class MessageController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('/{id}/messages', name: 'api_livraisons_messages_list', methods: ['GET'])]
    public function list(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $livraison = $em->getRepository(Livraison::class)->find($id);
        if (!$livraison instanceof Livraison) {
            return $this->error('Livraison non trouvée.', 404);
        }
        if (!$this->canAccessMessages($livraison, $user)) {
            return $this->error('Accès refusé.', 403);
        }
        $messages = $em->getRepository(Message::class)->findByLivraison($livraison->getId());
        return new JsonResponse(array_map(fn(Message $m) => EntitySerializer::message($m), $messages));
    }

    #[Route('/{id}/messages', name: 'api_livraisons_messages_create', methods: ['POST'])]
    public function create(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            if (!$this->canAccessMessages($livraison, $user)) {
                return $this->error('Accès refusé.', 403);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $contenu = trim((string) ($data['contenu'] ?? ''));
            if ($contenu === '') {
                return $this->error('Le contenu du message est requis.', 400);
            }
            $message = new Message();
            $message->setLivraison($livraison);
            $message->setExpediteur($user);
            $message->setContenu($contenu);
            $em->persist($message);
            $em->flush();
            return new JsonResponse(EntitySerializer::message($message), JsonResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/messages/lu', name: 'api_livraisons_messages_mark_read', methods: ['PUT'])]
    public function markRead(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            if (!$this->canAccessMessages($livraison, $user)) {
                return $this->error('Accès refusé.', 403);
            }
            $messages = $em->getRepository(Message::class)->findByLivraison($livraison->getId());
            $updated = 0;
            foreach ($messages as $message) {
                if ($message->getExpediteur()?->getId() !== $user->getId() && !$message->isLu()) {
                    $message->marquerCommeLu();
                    $updated++;
                }
            }
            $em->flush();
            return new JsonResponse(['updated' => $updated]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    private function canAccessMessages(Livraison $livraison, User $user): bool
    {
        if ($user->getRole() === 'admin') {
            return true;
        }
        if ($livraison->getLivreur()?->getId() === $user->getId()) {
            return true;
        }
        if ($livraison->getCommande()?->getAcheteur()?->getId() === $user->getId()) {
            return true;
        }
        return false;
    }
}
