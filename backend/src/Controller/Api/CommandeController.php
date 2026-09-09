<?php

namespace App\Controller\Api;

use App\Entity\Acheteur;
use App\Entity\Boutique;
use App\Entity\Commande;
use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use App\Service\CommandeService;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitySerializer $serializer,
        private readonly CommandeService $commandeService,
    ) {
    }

    /**
     * Passe une commande (toutes les lignes liées à une boutique).
     *
     * Body attendu :
     * {
     *   "boutiqueId": 1,
     *   "items": [{"produitId": 2, "couleur": "Noir", "taille": "M", "quantite": 1}]
     * }
     */
    #[Route('', name: 'api_commandes_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $acheteur = $this->getUser();
        if (!$acheteur instanceof Acheteur) {
            return $this->json(['error' => 'Seul un acheteur (client) peut passer commande.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $boutique = $this->entityManager->getRepository(Boutique::class)->find($payload['boutiqueId'] ?? 0);
        if (!$boutique) {
            return $this->json(['error' => 'Boutique introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $commande = $this->commandeService->passerCommande($acheteur, $boutique, $payload['items'] ?? []);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['commande' => $this->serializer->commande($commande)], Response::HTTP_CREATED);
    }

    /**
     * Commandes de l'acheteur connecté.
     */
    #[Route('/me', name: 'api_commandes_mes', methods: ['GET'])]
    public function mesCommandes(): JsonResponse
    {
        $acheteur = $this->getUser();
        if (!$acheteur instanceof Acheteur) {
            return $this->json(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        /** @var CommandeRepository $repository */
        $repository = $this->entityManager->getRepository(Commande::class);
        $commandes = $repository->findByAcheteur($acheteur->getId());

        return $this->json([
            'commandes' => array_map(fn (Commande $c) => $this->serializer->commande($c), $commandes),
        ]);
    }

    /**
     * Commandes de la boutique du vendeur connecté.
     */
    #[Route('/boutique/mes', name: 'api_commandes_boutique', methods: ['GET'])]
    public function commandesBoutique(): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        if (!$boutique) {
            return $this->json(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        /** @var CommandeRepository $repository */
        $repository = $this->entityManager->getRepository(Commande::class);
        $commandes = $repository->findByBoutique($boutique->getId());

        return $this->json([
            'commandes' => array_map(fn (Commande $c) => $this->serializer->commande($c), $commandes),
        ]);
    }

    /**
     * Transition de statut : en_attente → expediee → livree (ou annulee).
     * L'ouverture des avis est déclenchée au passage en "livree".
     */
    #[Route('/{id}/statut', name: 'api_commandes_statut', methods: ['PUT'])]
    public function statut(int $id, Request $request): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);
        if (!$boutique || !$commande || $commande->getBoutique()->getId() !== $boutique->getId()) {
            return $this->json(['error' => 'Commande introuvable ou non autorisée.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $statut = StatutCommande::tryFrom((string) ($payload['statut'] ?? ''));
        if (!$statut) {
            return $this->json(['error' => 'Statut invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $commande = $this->commandeService->changerStatut($commande, $statut);

        return $this->json(['commande' => $this->serializer->commande($commande)]);
    }

    private function boutiqueAutorisee(): ?Boutique
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Vendeur) {
            return null;
        }
        return $user->getBoutiqueActive();
    }
}