<?php

namespace App\Controller\Api;

use App\Entity\Administrateur;
use App\Entity\Litige;
use App\Enum\StatutLitige;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/litiges')]
class LitigeController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('', name: 'api_admin_litiges_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'admin');
        $litiges = $em->getRepository(Litige::class)->findAll();
        return new JsonResponse(array_map(fn(Litige $l) => EntitySerializer::litige($l, $em), $litiges));
    }

    #[Route('/{id}', name: 'api_admin_litiges_show', methods: ['GET'])]
    public function show(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'admin');
        $litige = $em->getRepository(Litige::class)->find($id);
        if (!$litige instanceof Litige) {
            return $this->error('Litige non trouvé.', 404);
        }
        return new JsonResponse(EntitySerializer::litige($litige, $em));
    }

    #[Route('/{id}', name: 'api_admin_litiges_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $litige = $em->getRepository(Litige::class)->find($id);
            if (!$litige instanceof Litige) {
                return $this->error('Litige non trouvé.', 404);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $statut = $data['statut'] ?? null;
            if ($statut === StatutLitige::EN_TRAITEMENT->value) {
                $litige->traiter();
            } elseif ($statut === StatutLitige::RESOLU->value) {
                if ($litige->getStatut() === StatutLitige::OUVERT->value) {
                    $litige->traiter();
                }
                $litige->resoudre($data['resolution'] ?? null);
            } elseif ($statut === StatutLitige::REJETE->value) {
                $litige->setStatut(StatutLitige::REJETE->value);
            }
            if (array_key_exists('resolution', $data) && $litige->getStatut() !== StatutLitige::RESOLU->value) {
                $litige->setResolution($data['resolution']);
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::litige($litige, $em));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
