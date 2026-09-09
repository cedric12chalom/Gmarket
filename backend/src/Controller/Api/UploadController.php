<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Upload d'image en base64 (photos produits, bannière/logo, photo de profil).
 * Renvoie une URL publique vers l'image stockée dans public/uploads.
 */
#[Route('/api/uploads')]
class UploadController extends AbstractController
{
    #[Route('', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $data = (string) ($payload['data'] ?? '');
        if ($data === '') {
            return $this->json(['error' => 'Aucune donnée d\'image fournie.'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->nettoyerDataUrl($data);
        $info = getimagesizefromstring($data);
        if ($info === false) {
            return $this->json(['error' => 'Données image invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $extension = match ($info['mime']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $nom = bin2hex(random_bytes(12)) . '.' . $extension;
        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir . '/' . $nom, $data);

        return $this->json(['url' => '/uploads/' . $nom], Response::HTTP_CREATED);
    }

    private function nettoyerDataUrl(string $data): string
    {
        if (str_contains($data, 'base64,')) {
            $data = substr($data, (int) strpos($data, 'base64,') + 7);
        }
        $decoded = base64_decode($data, true);
        return $decoded === false ? '' : $decoded;
    }
}