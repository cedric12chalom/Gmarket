<?php

namespace App\Controller\Api;

use App\Entity\DemandeProduit;
use App\Enum\Role;
use App\Enum\StatutDemande;
use App\Service\EntitySerializer;
use App\Service\NotificationService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/demandes-produit')]
class DemandeProduitController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;
    private NotificationService $notificationService;

    public function __construct(TokenService $tokenService, NotificationService $notificationService)
    {
        $this->tokenService = $tokenService;
        $this->notificationService = $notificationService;
    }

    #[Route('', name: 'api_demandes_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        if ($user->getRole() === Role::ADMIN->value) {
            $demandes = $em->getRepository(DemandeProduit::class)->findBy([], ['dateCreation' => 'DESC']);
        } else {
            $this->requireRole($user, Role::PRODUCTEUR->value);
            $demandes = $em->getRepository(DemandeProduit::class)->findBy(['producteur' => $user], ['dateCreation' => 'DESC']);
        }
        return new JsonResponse(array_map(fn(DemandeProduit $d) => EntitySerializer::demandeProduit($d), $demandes));
    }

    #[Route('', name: 'api_demandes_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, Role::PRODUCTEUR->value);
            $data = json_decode($request->getContent(), true) ?? [];
            if (empty($data['nom_produit'])) {
                return $this->error('Le nom du produit est requis.', 400);
            }
            $demande = new DemandeProduit();
            $demande->setProducteur($user)
                ->setNomProduit($data['nom_produit'])
                ->setCategorieSuggeree($data['categorie_suggeree'] ?? null)
                ->setDescription($data['description'] ?? null)
                ->setUnite($data['unite'] ?? null);
            $em->persist($demande);
            $em->flush();

            $admins = $em->getRepository(\App\Entity\Administrateur::class)->findAll();
            foreach ($admins as $admin) {
                $this->notificationService->notifier(
                    $admin,
                    'Nouvelle demande de produit',
                    sprintf('%s %s demande l\'ajout du produit "%s".', $user->getPrenom(), $user->getNom(), $demande->getNomProduit())
                );
            }
            $em->flush();

            return new JsonResponse(EntitySerializer::demandeProduit($demande), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/traiter', name: 'api_demandes_traiter', methods: ['PUT'])]
    public function traiter(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, Role::ADMIN->value);
            $demande = $em->getRepository(DemandeProduit::class)->find($id);
            if (!$demande instanceof DemandeProduit) {
                return $this->error('Demande non trouvée.', 404);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            $decision = $data['statut'] ?? '';
            if (!in_array($decision, [StatutDemande::APPROUVEE->value, StatutDemande::REFUSEE->value], true)) {
                return $this->error('Statut invalide. Valeurs acceptées : approuvee, refusee.', 400);
            }
            $demande->setStatut($decision);
            $demande->setDateTraitement(new \DateTime());
            if ($decision === StatutDemande::REFUSEE->value) {
                $demande->setMotifRefus($data['motif_refus'] ?? null);
            }
            $em->flush();

            $producteur = $demande->getProducteur();
            if ($producteur) {
                $this->notificationService->notifierEtEnvoyer(
                    $producteur,
                    'Demande de produit ' . ($decision === StatutDemande::APPROUVEE->value ? 'approuvée' : 'refusée'),
                    sprintf(
                        'Votre demande pour "%s" a été %s.%s',
                        $demande->getNomProduit(),
                        $decision === StatutDemande::APPROUVEE->value ? 'approuvée' : 'refusée',
                        $demande->getMotifRefus() ? ' Motif : ' . $demande->getMotifRefus() : ''
                    )
                );
            }

            return new JsonResponse(EntitySerializer::demandeProduit($demande));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
