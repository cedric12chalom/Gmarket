<?php

namespace App\Controller\Api;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\FakePaymentService;
use App\Service\EntitySerializer;
use App\Service\PaymentProviderInterface;
use App\Service\TokenService;
use App\Service\WalletService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/utilisateurs')]
class WalletController extends AbstractController
{
    use TokenAwareControllerTrait;

    public function __construct(
        private TokenService $tokenService,
        private WalletService $walletService,
        private PaymentProviderInterface $paymentProvider,
    ) {}

    #[Route('/moi/solde', name: 'api_utilisateurs_solde', methods: ['GET'])]
    public function solde(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $transactions = $em->getRepository(Transaction::class)->findByUtilisateur($user->getId());
            return new JsonResponse([
                'solde' => (float) ($user->getSolde() ?? '0.00'),
                'transactions' => array_map(fn(Transaction $t) => [
                    'id' => $t->getId(),
                    'type' => $t->getType(),
                    'montant' => (float) $t->getMontant(),
                    'motif' => $t->getMotif(),
                    'reference' => $t->getReference(),
                    'solde_apres' => (float) $t->getSoldeApres(),
                    'date_creation' => $t->getDateCreation()?->format('c'),
                ], $transactions),
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/moi/solde/recharger', name: 'api_utilisateurs_solde_recharger', methods: ['POST'])]
    public function recharger(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $montant = (float) ($data['montant'] ?? 0);
            $numero = trim((string) ($data['numero'] ?? ''));
            if ($montant <= 0) { return $this->error('Montant invalide.', 400); }
            if ($numero === '') { return $this->error('Numéro Mobile Money requis.', 400); }

            $collecte = $this->paymentProvider->collect(
                $montant, $numero,
                'Recharge solde TerraLink',
                'RECHARGE-' . $user->getId() . '-' . time()
            );
            $reference = $collecte['reference'] ?? null;
            if (!$reference) { return $this->error('Échec de la demande Campay.', 502); }

            $statut = $this->paymentProvider->getTransactionStatus($reference);
            if (($statut['status'] ?? null) === FakePaymentService::STATUT_SUCCESSFUL || ($statut['status'] ?? null) === \App\Service\FakePaymentService::STATUT_SUCCESSFUL) {
                $tx = $this->walletService->credit($user, (string) $montant, 'recharge', $reference);
                return new JsonResponse([
                    'solde' => (float) $tx->getSoldeApres(),
                    'transaction' => ['id' => $tx->getId(), 'montant' => (float) $tx->getMontant(), 'type' => $tx->getType(), 'motif' => $tx->getMotif()],
                ]);
            }
            return new JsonResponse(['message' => 'Paiement en attente de confirmation.', 'reference' => $reference]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/moi/solde/recharger/{reference}/verifier', name: 'api_utilisateurs_solde_recharger_verifier', methods: ['PUT'])]
    public function verifierRecharge(string $reference, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));

            // Idempotency: if a credit transaction already exists for this reference, it's already been processed.
            $existing = $em->getRepository(Transaction::class)->findByReference($reference);
            if ($existing) {
                return new JsonResponse([
                    'statut' => 'deja_credite',
                    'solde' => (float) ($user->getSolde() ?? '0.00'),
                    'transaction' => [
                        'id' => $existing->getId(),
                        'montant' => (float) $existing->getMontant(),
                        'type' => $existing->getType(),
                        'motif' => $existing->getMotif(),
                    ],
                ]);
            }

            // Live re-check against the active provider — never trust client-supplied status.
            $statut = $this->paymentProvider->getTransactionStatus($reference);
            $status = $statut['status'] ?? null;

            if ($status === FakePaymentService::STATUT_SUCCESSFUL || $status === \App\Service\FakePaymentService::STATUT_SUCCESSFUL) {
                $montant = $statut['amount'] ?? null;
                if ($montant === null) {
                    return $this->error('Montant introuvable pour cette référence Campay.', 502);
                }
                // Campay uses integer amounts; normalise to decimal string for bcadd.
                $montantStr = number_format((float) $montant, 2, '.', '');
                $tx = $this->walletService->credit($user, $montantStr, 'recharge', $reference);
                return new JsonResponse([
                    'statut' => 'succes',
                    'solde' => (float) $tx->getSoldeApres(),
                    'transaction' => [
                        'id' => $tx->getId(),
                        'montant' => (float) $tx->getMontant(),
                        'type' => $tx->getType(),
                        'motif' => $tx->getMotif(),
                    ],
                ]);
            }

            if ($status === FakePaymentService::STATUT_FAILED || $status === \App\Service\FakePaymentService::STATUT_FAILED) {
                return new JsonResponse([
                    'statut' => 'echec',
                    'message' => 'Le paiement a échoué.',
                    'reference' => $reference,
                ]);
            }

            return new JsonResponse([
                'statut' => 'en_attente',
                'message' => 'Paiement en attente de confirmation.',
                'reference' => $reference,
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/moi/position', name: 'api_utilisateurs_position', methods: ['PUT'])]
    public function position(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $lat = $data['latitude'] ?? null;
            $lng = $data['longitude'] ?? null;
            if ($lat === null || $lng === null) {
                return $this->error('latitude et longitude requis.', 400);
            }
            $lat = (float) $lat;
            $lng = (float) $lng;
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return $this->error('Coordonnées hors limites.', 400);
            }
            $user->setLatitude((string) $lat);
            $user->setLongitude((string) $lng);
            $em->flush();
            return new JsonResponse(['latitude' => $lat, 'longitude' => $lng]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}