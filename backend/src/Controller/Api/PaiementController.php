<?php

namespace App\Controller\Api;

use App\Entity\Commande;
use App\Entity\Livraison;
use App\Entity\Paiement;
use App\Entity\User;
use App\Enum\ModeRecuperation;
use App\Enum\StatutCommande;
use App\Enum\StatutPaiement;
use App\Service\FakePaymentService;
use App\Service\EntitySerializer;
use App\Service\PaymentProviderInterface;
use App\Service\TokenService;
use App\Service\WalletService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/paiements')]
class PaiementController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;
    private PaymentProviderInterface $paymentProvider;

    public function __construct(TokenService $tokenService, PaymentProviderInterface $paymentProvider)
    {
        $this->tokenService = $tokenService;
        $this->paymentProvider = $paymentProvider;
    }

    #[Route('', name: 'api_paiements_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'admin');
        $paiements = $em->getRepository(Paiement::class)->findBy([], ['datePaiement' => 'DESC']);
        return new JsonResponse(array_map(fn(Paiement $p) => EntitySerializer::paiement($p), $paiements));
    }

    /**
     * Crée un paiement et déclenche immédiatement une demande Mobile Money Campay
     * (le client reçoit un push USSD à valider sur son téléphone). Le paiement reste
     * "en_attente" tant que Campay n'a pas confirmé : voir verifier() et webhook().
     */
    #[Route('', name: 'api_paiements_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, WalletService $walletService): JsonResponse
    {
        $em->getConnection()->beginTransaction();
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $commande = isset($data['commande_id']) ? $em->getRepository(Commande::class)->find($data['commande_id']) : null;
            if (!$commande instanceof Commande) {
                return $this->error('Commande non trouvée.', 404);
            }
            if ($commande->getAcheteur()?->getId() !== $user->getId()) {
                return $this->error('Accès refusé.', 403);
            }
            $montant = (float) ($data['montant'] ?? 0);
            $total = (float) $commande->getMontantTotal();
            if (abs($montant - $total) > 0.01) {
                return $this->error('Le montant du paiement ne correspond pas au montant total de la commande.', 400);
            }
            $methode = strtolower((string) ($data['methode'] ?? 'campay'));
            $numero = trim((string) ($data['numero'] ?? ''));
            if ($methode === 'campay' && $numero === '') {
                return $this->error('Le numéro de téléphone Mobile Money est requis.', 400);
            }
            $existing = $em->getRepository(Paiement::class)->findBloquantByCommande($commande->getId());
            if ($existing instanceof Paiement) {
                return $this->error('Un paiement existe déjà pour cette commande.', 409);
            }

            $paiement = new Paiement();
            $paiement->setCommande($commande);
            $paiement->setMontant(number_format($total, 2, '.', ''));
            $paiement->setMethode($methode);
            $paiement->setNumero($methode === 'campay' ? $numero : null);
            $em->persist($paiement);
            $em->flush();

            if ($methode === 'solde') {
                $soldeActuel = (string) ($user->getSolde() ?? '0.00');
                if (bccomp($soldeActuel, number_format($total, 2, '.', ''), 2) < 0) {
                    $em->getConnection()->rollBack();
                    return $this->error('Solde insuffisant pour payer cette commande.', 402);
                }
                $walletService->debit($user, number_format($total, 2, '.', ''), 'paiement commande', $paiement->getReference(), false);
                $paiement->valider();
                $this->finaliserCommandePayee($commande, $em);
                $em->flush();
                $em->getConnection()->commit();
                return new JsonResponse(EntitySerializer::paiement($paiement), Response::HTTP_CREATED);
            }

            try {
                if ($this->paymentProvider->isSandbox() && (float) $commande->getMontantTotal() > 25) {
                    $em->getConnection()->rollBack();
                    return $this->error(
                        'Mode test Campay : le montant total (' . (float) $commande->getMontantTotal() . ' FCFA) dépasse la limite sandbox de 25 FCFA. Réduisez les quantités ou choisissez le retrait (gratuit) plutôt que la livraison.',
                        400
                    );
                }
                $collecte = $this->paymentProvider->collect(
                    $montant,
                    $numero,
                    'Commande #' . $commande->getId() . ' - TerraLink',
                    $paiement->getReference()
                );
                $paiement->setCampayReference($collecte['reference'] ?? null);
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Throwable $e) {
                $paiement->echouer();
                $em->flush();
                $em->getConnection()->commit();
                return $this->error('Impossible de contacter Campay : ' . $e->getMessage(), 502);
            }

            return new JsonResponse(EntitySerializer::paiement($paiement), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $em->getConnection()->rollBack();
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/verifier', name: 'api_paiements_verifier', methods: ['GET', 'PUT'])]
    public function verifier(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $paiement = $em->getRepository(Paiement::class)->find($id);
            if (!$paiement instanceof Paiement) {
                return $this->error('Paiement non trouvé.', 404);
            }
            $commande = $paiement->getCommande();
            if ($commande->getAcheteur()?->getId() !== $user->getId() && $user->getRole() !== 'admin') {
                return $this->error('Accès refusé.', 403);
            }

            if ($paiement->getStatut() === StatutPaiement::EN_ATTENTE->value && $paiement->getCampayReference()) {
                $this->rafraichirDepuisCampay($paiement, $em);
            }

            return new JsonResponse(EntitySerializer::paiement($paiement));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/campay/webhook', name: 'api_paiements_campay_webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $expectedToken = $_ENV['CAMPAY_WEBHOOK_TOKEN'] ?? '';
        if ($expectedToken !== '' && $request->query->get('token') !== $expectedToken) {
            return $this->error('Non autorisé.', 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $reference = $data['reference'] ?? $data['external_reference'] ?? null;
        if (!$reference) {
            return new JsonResponse(['status' => 'ignored']);
        }

        $paiement = $em->getRepository(Paiement::class)->findOneBy(['campayReference' => $reference])
            ?? $em->getRepository(Paiement::class)->findOneBy(['reference' => $reference]);

        if (!$paiement instanceof Paiement || $paiement->getStatut() !== StatutPaiement::EN_ATTENTE->value) {
            return new JsonResponse(['status' => 'ignored']);
        }

        try {
            $this->rafraichirDepuisCampay($paiement, $em);
        } catch (\Throwable $e) {
            // On répond quand même 200 pour éviter que Campay ne boucle les tentatives.
        }

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/commande/{commandeId}', name: 'api_paiements_by_commande', methods: ['GET'])]
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
            && !$this->isProducerForCommande($commande, $user)
        ) {
            return $this->error('Accès refusé.', 403);
        }
        $paiement = $em->getRepository(Paiement::class)->findOneByCommande($commandeId);
        if (!$paiement instanceof Paiement) {
            return $this->error('Paiement non trouvé.', 404);
        }
        return new JsonResponse(EntitySerializer::paiement($paiement));
    }

    private function rafraichirDepuisCampay(Paiement $paiement, EntityManagerInterface $em): void
    {
        $statutCampay = $this->paymentProvider->getTransactionStatus($paiement->getCampayReference());
        $statut = $statutCampay['status'] ?? null;

        if ($statut === FakePaymentService::STATUT_SUCCESSFUL) {
            $paiement->valider();
            $commande = $paiement->getCommande();
            $this->finaliserCommandePayee($commande, $em);
            $em->flush();
        } elseif ($statut === FakePaymentService::STATUT_FAILED) {
            $paiement->echouer();
            $em->flush();
        }
    }

    private function finaliserCommandePayee(Commande $commande, EntityManagerInterface $em): void
    {
        $commande->setStatut(StatutCommande::PAYEE->value);
        if ($commande->getModeRecuperation() === ModeRecuperation::LIVRAISON->value) {
            $existingLivraison = $em->getRepository(Livraison::class)->findOneBy(['commande' => $commande->getId()]);
            if (!$existingLivraison instanceof Livraison) {
                $livraison = new Livraison();
                $livraison->setCommande($commande);
                $livraison->setAdresseLivraison($commande->getAcheteur()?->getAdresseLivraison() ?? '');
                $livraison->setFrais($commande->getFraisLivraison());
                $em->persist($livraison);
            }
        }
    }

    private function isProducerForCommande(Commande $commande, User $user): bool
    {
        foreach ($commande->getLignes() as $ligne) {
            $lot = $ligne->getLot();
            if ($lot && $lot->getProducteur()?->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }
}
