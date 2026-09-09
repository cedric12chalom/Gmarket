<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

trait TokenAwareControllerTrait
{
    private function getCurrentUser(Request $request, EntityManagerInterface $em, TokenService $tokenService): ?User
    {
        $header = $request->headers->get('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $payload = $tokenService->decode(substr($header, 7));
        if (!$payload || !isset($payload['id'])) {
            return null;
        }
        $user = $em->getRepository(User::class)->find($payload['id']);
        if (!$user instanceof User || $user->getEmail() !== ($payload['email'] ?? null) || !$user->isActif()) {
            return null;
        }
        return $user;
    }

    private function requireUser(?User $user): User
    {
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }
        return $user;
    }

    private function requireRole(User $user, string $role): void
    {
        if ($user->getRole() !== $role) {
            throw new AccessDeniedHttpException('Accès refusé pour ce rôle.');
        }
    }

    private function error(string $message, int $code): JsonResponse
    {
        return new JsonResponse(['message' => $message, 'code' => $code], $code);
    }

    private function exceptionResponse(\Throwable $e): JsonResponse
    {
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            return $this->error($e->getMessage(), $e->getStatusCode());
        }
        return $this->error($e->getMessage(), $e->getCode() >= 400 ? $e->getCode() : 500);
    }
}
