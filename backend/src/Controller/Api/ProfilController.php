<?php

namespace App\Controller\Api;

use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profil')]
class ProfilController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitySerializer $serializer,
    ) {
    }

    /**
     * Met à jour le profil (photo, téléphone, réseaux sociaux).
     */
    #[Route('', name: 'api_profil_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('photo', $payload)) {
            $user->setPhoto($payload['photo'] !== '' ? $payload['photo'] : null);
        }
        if (array_key_exists('telephone', $payload)) {
            $user->setTelephone($payload['telephone'] !== '' ? $payload['telephone'] : null);
        }
        if ($user instanceof \App\Entity\Acheteur || $user instanceof \App\Entity\Vendeur) {
            if (array_key_exists('prenom', $payload)) {
                $user->setPrenom($payload['prenom']);
            }
            if (array_key_exists('nom', $payload)) {
                $user->setNom($payload['nom']);
            }
        }

        $user->setTiktok(array_key_exists('tiktok', $payload) ? ($payload['tiktok'] ?: null) : $user->getTiktok());
        $user->setInstagram(array_key_exists('instagram', $payload) ? ($payload['instagram'] ?: null) : $user->getInstagram());
        $user->setSnapchat(array_key_exists('snapchat', $payload) ? ($payload['snapchat'] ?: null) : $user->getSnapchat());
        $user->setFacebook(array_key_exists('facebook', $payload) ? ($payload['facebook'] ?: null) : $user->getFacebook());

        $this->entityManager->flush();

        return $this->json(['user' => $this->serializer->user($user)]);
    }
}