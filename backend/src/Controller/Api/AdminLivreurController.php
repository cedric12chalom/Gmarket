<?php

namespace App\Controller\Api;

use App\Entity\Livreur;
use App\Enum\StatutVerificationLivreur;
use App\Service\EntitySerializer;
use App\Service\NotificationService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/livreurs')]
class AdminLivreurController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;
    private NotificationService $notificationService;

    public function __construct(TokenService $tokenService, NotificationService $notificationService)
    {
        $this->tokenService = $tokenService;
        $this->notificationService = $notificationService;
    }

    /**
     * Liste les livreurs en attente de vérification (ou tous si ?statut=).
     */
    #[Route('/verification', name: 'api_admin_livreurs_verification', methods: ['GET'])]
    public function verification(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');

            $statut = $request->query->get('statut');
            $statuts = is_string($statut) && $statut !== ''
                ? [$statut]
                : [StatutVerificationLivreur::EN_ATTENTE->value, StatutVerificationLivreur::VERIFIE->value];

            $livreurs = $em->getRepository(Livreur::class)->findByStatutVerification($statuts);
            return new JsonResponse(array_map(fn(Livreur $l) => EntitySerializer::user($l), $livreurs));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/verifier', name: 'api_admin_livreurs_verifier', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function verifier(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $livreur = $this->findLivreur($id, $em);
            if ($livreur === null) {
                return $this->error('Livreur non trouvé.', 404);
            }
            $livreur->setStatutVerification(StatutVerificationLivreur::VERIFIE->value);
            $livreur->setVerifieLe(new \DateTime());
            $em->flush();
            return new JsonResponse(EntitySerializer::user($livreur));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/valider', name: 'api_admin_livreurs_valider', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function valider(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $livreur = $this->findLivreur($id, $em);
            if ($livreur === null) {
                return $this->error('Livreur non trouvé.', 404);
            }
            $livreur->setStatutVerification(StatutVerificationLivreur::VALIDE->value);
            $livreur->setMotifRefus(null);
            $livreur->setValideLe(new \DateTime());
            $this->notificationService->notifierEtEnvoyer($livreur, 'Dossier validé', 'Votre dossier de livraison a été validé. Vous pouvez désormais accepter des missions.');
            $em->flush();
            return new JsonResponse(EntitySerializer::user($livreur));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/refuser', name: 'api_admin_livreurs_refuser', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function refuser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $livreur = $this->findLivreur($id, $em);
            if ($livreur === null) {
                return $this->error('Livreur non trouvé.', 404);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $motif = trim((string) ($data['motif'] ?? ''));
            if ($motif === '') {
                return $this->error('Un motif de refus est requis.', 400);
            }
            $livreur->setStatutVerification(StatutVerificationLivreur::REFUSE->value);
            $livreur->setMotifRefus($motif);
            $livreur->setValideLe(null);
            $this->notificationService->notifierEtEnvoyer($livreur, 'Dossier refusé', 'Votre dossier de livraison a été refusé. Motif : ' . $motif);
            $em->flush();
            return new JsonResponse(EntitySerializer::user($livreur));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    private function findLivreur(int $id, EntityManagerInterface $em): ?Livreur
    {
        $livreur = $em->getRepository(Livreur::class)->find($id);
        return $livreur instanceof Livreur ? $livreur : null;
    }
}
