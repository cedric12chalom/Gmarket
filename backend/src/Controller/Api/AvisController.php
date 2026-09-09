<?php

namespace App\Controller\Api;

use App\Repository\AvisRepository;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AvisController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitySerializer $serializer,
    ) {
    }

    /**
     * Avis publics d'une boutique.
     */
    #[Route('/boutiques/{id}/avis', name: 'api_avis_boutique', methods: ['GET'])]
    public function byBoutique(int $id): JsonResponse
    {
        /** @var AvisRepository $repository */
        $repository = $this->entityManager->getRepository(\App\Entity\Avis::class);
        $avis = $repository->findByBoutique($id);
        $moyenne = $repository->moyenneNote($id);

        return $this->json([
            'avis' => array_map(fn ($a) => $this->serializer->avis($a), $avis),
            'moyenne' => $moyenne !== null ? round($moyenne, 1) : null,
            'total' => count($avis),
        ]);
    }

    /**
     * Laisse un avis sur une commande livrée (un seul avis par commande).
     */
    #[Route('/commandes/{id}/avis', name: 'api_avis_create', methods: ['POST'])]
    public function create(int $id, Request $request): JsonResponse
    {
        $acheteur = $this->getUser();
        if (!$acheteur instanceof \App\Entity\Acheteur) {
            return $this->json(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $commande = $this->entityManager->getRepository(\App\Entity\Commande::class)->find($id);
        if (!$commande || $commande->getAcheteur()->getId() !== $acheteur->getId()) {
            return $this->json(['error' => 'Commande introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if (!$commande->getStatut()->ouvreAvis()) {
            return $this->json(['error' => 'Les avis ne sont ouverts que pour les commandes livrées.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($commande->getAvis()) {
            return $this->json(['error' => 'Un avis existe déjà pour cette commande.'], Response::HTTP_CONFLICT);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $note = (int) ($payload['note'] ?? 0);
        if ($note < 1 || $note > 5) {
            return $this->json(['error' => 'La note doit être comprise entre 1 et 5.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $avis = new \App\Entity\Avis();
        $avis->setCommande($commande);
        $avis->setBoutique($commande->getBoutique());
        $avis->setAcheteur($acheteur);
        $avis->setNote($note);
        $avis->setCommentaire($payload['commentaire'] ?? null);

        $this->entityManager->persist($avis);
        $this->entityManager->flush();

        return $this->json(['avis' => $this->serializer->avis($avis)], Response::HTTP_CREATED);
    }
}