<?php

namespace App\Controller\Api;

use App\Entity\Commande;
use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Entity\User;
use App\Service\EntitySerializer;
use App\Service\NotificationService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/livraisons')]
class LivraisonController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;
    private NotificationService $notificationService;

    public function __construct(TokenService $tokenService, NotificationService $notificationService)
    {
        $this->tokenService = $tokenService;
        $this->notificationService = $notificationService;
    }

    #[Route('', name: 'api_livraisons_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        if ($user->getRole() === 'admin') {
            $livraisons = $em->getRepository(Livraison::class)->findAll();
        } elseif ($user instanceof Livreur) {
            $livraisons = $em->getRepository(Livraison::class)->findByLivreur($user->getId());
        } else {
            $livraisons = [];
        }
        return new JsonResponse(array_map(fn(Livraison $l) => EntitySerializer::livraison($l), $livraisons));
    }

    #[Route('/disponibles', name: 'api_livraisons_disponibles', methods: ['GET'])]
    public function disponibles(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'livreur');
        if (!$user instanceof Livreur) {
            return $this->error('Rôle livreur requis.', 403);
        }
        if (!$user->estEligibleAuxMissions()) {
            return $this->error('Votre dossier de livraison doit être validé par un administrateur avant de recevoir des missions.', 403);
        }
        $livraisons = $em->getRepository(Livraison::class)->findDisponibles();
        // RG15 : on ne propose au livreur que les livraisons correspondant à son type de transport.
        $livraisons = array_values(array_filter(
            $livraisons,
            fn(Livraison $l) => $this->getTransportRequis($l) === null
                || $this->getTransportRequis($l) === $user->getTypeTransport()
        ));
        return new JsonResponse(array_map(fn(Livraison $l) => EntitySerializer::livraison($l), $livraisons));
    }

    #[Route('/{id}', name: 'api_livraisons_show', methods: ['GET'])]
    public function show(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $livraison = $em->getRepository(Livraison::class)->find($id);
        if (!$livraison instanceof Livraison) {
            return $this->error('Livraison non trouvée.', 404);
        }
        if (
            $user->getRole() === 'admin'
            || $livraison->getLivreur()?->getId() === $user->getId()
            || $this->isProducerOrBuyerForCommande($livraison->getCommande(), $user)
        ) {
            return new JsonResponse(EntitySerializer::livraison($livraison));
        }
        return $this->error('Accès refusé.', 403);
    }

    #[Route('/livreur/{userId}', name: 'api_livraisons_by_livreur', methods: ['GET'])]
    public function byLivreur(int $userId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'livreur');
        if ($user->getId() !== $userId) {
            return $this->error('Vous ne pouvez consulter que vos propres missions.', 403);
        }
        $livraisons = $em->getRepository(Livraison::class)->findByLivreur($user->getId());
        return new JsonResponse(array_map(fn(Livraison $l) => EntitySerializer::livraison($l), $livraisons));
    }

    #[Route('/commande/{commandeId}', name: 'api_livraisons_by_commande', methods: ['GET'])]
    public function byCommande(int $commandeId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $commande = $em->getRepository(Commande::class)->find($commandeId);
        if (!$commande instanceof Commande) {
            return $this->error('Commande non trouvée.', 404);
        }
        if (
            $user->getRole() !== 'admin'
            && $commande->getAcheteur()?->getId() !== $user->getId()
            && !$this->isProducerOrBuyerForCommande($commande, $user)
        ) {
            return $this->error('Accès refusé.', 403);
        }
        $livraison = $em->getRepository(Livraison::class)->findOneByCommande($commandeId);
        if (!$livraison instanceof Livraison) {
            return $this->error('Livraison non trouvée.', 404);
        }
        return new JsonResponse(EntitySerializer::livraison($livraison));
    }

    #[Route('/{id}/accepter', name: 'api_livraisons_accepter', methods: ['PUT'])]
    public function accepter(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'livreur');
            if (!$user instanceof Livreur) {
                return $this->error('Rôle livreur requis.', 403);
            }
            if (!$user->estEligibleAuxMissions()) {
                return $this->error('Votre dossier de livraison doit être validé par un administrateur avant d\'accepter une mission.', 403);
            }
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            // RG15 : vérification du type de transport requis par la catégorie du produit.
            $typeTransportRequis = $this->getTransportRequis($livraison);
            if ($typeTransportRequis && $user->getTypeTransport() !== $typeTransportRequis) {
                return $this->error('RG15 : votre type de transport ne correspond pas à celui exigé par la livraison.', 403);
            }
            $livraison->accepter($user);
            $em->flush();
            $acheteur = $livraison->getCommande()?->getAcheteur();
            if ($acheteur instanceof User) {
                $this->notificationService->notifierEtEnvoyer(
                    $acheteur,
                    'Livreur en route',
                    sprintf('Votre livreur %s %s a accepté la mission pour votre commande #%d.', $user->getPrenom(), $user->getNom(), $livraison->getCommande()->getId())
                );
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::livraison($livraison));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/assigner', name: 'api_livraisons_assigner', methods: ['PUT'])]
    public function assigner(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            $commande = $livraison->getCommande();
            if (!$commande instanceof Commande || $commande->getAcheteur()?->getId() !== $user->getId()) {
                return $this->error('Vous ne pouvez assigner que vos propres livraisons.', 403);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $livreurId = (int) ($data['livreur_id'] ?? 0);
            $livreur = $em->getRepository(Livreur::class)->find($livreurId);
            if (!$livreur instanceof Livreur) {
                return $this->error('Livreur non trouvé.', 404);
            }
            if (!$livreur->isActif() || !$livreur->isDisponible()) {
                return $this->error('Ce livreur n\'est pas disponible.', 400);
            }
            if (!$livreur->estEligibleAuxMissions()) {
                return $this->error('Le dossier de ce livreur n\'a pas encore été validé.', 400);
            }
            $livraison->assignerParAcheteur($livreur);
            $em->flush();
            $this->notificationService->notifierEtEnvoyer(
                $livreur,
                'Nouvelle mission assignée',
                sprintf('Vous avez été assigné à la livraison #%d. Connectez-vous pour l\'accepter.', $livraison->getId())
            );
            $em->flush();
            return new JsonResponse(EntitySerializer::livraison($livraison));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/livrer', name: 'api_livraisons_livrer', methods: ['PUT'])]
    public function livrer(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $livraison = $em->getRepository(Livraison::class)->find($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Livraison non trouvée.', 404);
            }
            if ($livraison->getLivreur()?->getId() !== $user->getId()) {
                // L'acheteur peut aussi confirmer la livraison
                $commande = $livraison->getCommande();
                if (!$commande instanceof Commande || $commande->getAcheteur()?->getId() !== $user->getId()) {
                    return $this->error('Seul le livreur assigné ou l\'acheteur peut confirmer la livraison.', 403);
                }
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $photo = $data['photo_livraison'] ?? '';
            $livraison->confirmerLivraison($photo);
            $em->flush();

            // Le crédit du livreur et des producteurs est déclenché par la confirmation de
            // réception de l'acheteur (Partie 9) et non plus automatiquement ici.
            return new JsonResponse(EntitySerializer::livraison($livraison));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    private function getTransportRequis(Livraison $livraison): ?string
    {
        $commande = $livraison->getCommande();
        if (!$commande instanceof Commande) {
            return null;
        }
        foreach ($commande->getLignes() as $ligne) {
            $type = $ligne->getLot()?->getProduit()?->getCategorie()?->getTypeTransport();
            if ($type !== null) {
                return $type;
            }
        }
        return null;
    }

    private function isProducerOrBuyerForCommande(?Commande $commande, User $user): bool
    {
        if (!$commande instanceof Commande) {
            return false;
        }
        if ($commande->getAcheteur()?->getId() === $user->getId()) {
            return true;
        }
        foreach ($commande->getLignes() as $ligne) {
            $lot = $ligne->getLot();
            if ($lot && $lot->getProducteur()?->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }
}
