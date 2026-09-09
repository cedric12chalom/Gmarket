<?php

namespace App\Controller\Api;

use App\Entity\Abonnement;
use App\Entity\AbonnementSouscription;
use App\Entity\Vendeur;
use App\Enum\StatutAbonnement;
use App\Service\AbonnementService;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/abonnements')]
class AbonnementController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitySerializer $serializer,
        private readonly AbonnementService $abonnementService,
    ) {
    }

    /**
     * Liste des formules (paliers) d'abonnement disponibles.
     */
    #[Route('', name: 'api_abonnements_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $plans = $this->entityManager->getRepository(Abonnement::class)->findActifs();
        return $this->json([
            'essaiJours' => Abonnement::ESSAI_JOURS,
            'plans' => array_map(fn (Abonnement $a) => [
                'id' => $a->getId(),
                'nom' => $a->getNom(),
                'code' => $a->getCode(),
                'prix' => $a->getPrix(),
                'dureeJours' => $a->getDureeJours(),
                'avantages' => $a->getAvantages(),
            ], $plans),
        ]);
    }

    /**
     * Abonnement courant du vendeur connecté.
     */
    #[Route('/me', name: 'api_abonnements_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $vendeur = $this->getUser();
        if (!$vendeur instanceof Vendeur) {
            return $this->json(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $actuel = $vendeur->abonnementActuel();
        return $this->json([
            'peutVendre' => $this->abonnementService->peutVendre($vendeur),
            'actuel' => $actuel ? [
                'id' => $actuel->getId(),
                'nom' => $actuel->getAbonnement()->getNom(),
                'code' => $actuel->getAbonnement()->getCode(),
                'statut' => $actuel->getStatut()->value,
                'dateDebut' => $actuel->getDateDebut()?->format('c'),
                'dateFin' => $actuel->getDateFin()?->format('c'),
                'joursRestants' => $actuel->joursRestants(new \DateTimeImmutable()),
            ] : null,
        ]);
    }

    /**
     * Souscrire à une formule payante (le vendeur doit détenir une boutique).
     */
    #[Route('/souscrire', name: 'api_abonnements_souscrire', methods: ['POST'])]
    public function souscrire(Request $request): JsonResponse
    {
        $vendeur = $this->getUser();
        if (!$vendeur instanceof Vendeur) {
            return $this->json(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }
        if (!$vendeur->getBoutiqueActive()) {
            return $this->json(['error' => 'Créez d\'abord votre boutique.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $plan = $this->entityManager->getRepository(Abonnement::class)->find($payload['abonnementId'] ?? 0);
        if (!$plan || !$plan->isActif()) {
            return $this->json(['error' => 'Formule invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $souscription = $this->abonnementService->souscrire($vendeur, $plan, $plan->getDureeJours(), StatutAbonnement::ACTIF);

        return $this->json([
            'souscription' => [
                'id' => $souscription->getId(),
                'plan' => $plan->getNom(),
                'dateDebut' => $souscription->getDateDebut()?->format('c'),
                'dateFin' => $souscription->getDateFin()?->format('c'),
                'statut' => $souscription->getStatut()->value,
            ],
        ], Response::HTTP_CREATED);
    }
}