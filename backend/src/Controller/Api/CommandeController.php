<?php

namespace App\Controller\Api;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Litige;
use App\Entity\Livraison;
use App\Entity\Lot;
use App\Entity\Livreur;
use App\Entity\Paiement;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\ModeRecuperation;
use App\Enum\StatutCommande;
use App\Enum\StatutLivraison;
use App\Enum\StatutPaiement;
use App\Service\EntitySerializer;
use App\Service\GeoMatcherService;
use App\Service\LivraisonGeoService;
use App\Service\NotificationService;
use App\Service\TarificationLivraisonService;
use App\Service\TokenService;
use App\Service\WalletService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    use TokenAwareControllerTrait;

    private const DELAI_CONFIRMATION_HEURES = 24;
    private const DELAI_LIVRAISON_HEURES = 24;
    private const DELAI_RECEPTION_JOURS = 7;

    private TokenService $tokenService;
    private WalletService $walletService;
    private NotificationService $notificationService;
    private LivraisonGeoService $livraisonGeoService;
    private TarificationLivraisonService $tarificationLivraisonService;
    private GeoMatcherService $geoMatcherService;

    public function __construct(
        TokenService $tokenService,
        WalletService $walletService,
        NotificationService $notificationService,
        LivraisonGeoService $livraisonGeoService,
        TarificationLivraisonService $tarificationLivraisonService,
        GeoMatcherService $geoMatcherService
    ) {
        $this->tokenService = $tokenService;
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
        $this->livraisonGeoService = $livraisonGeoService;
        $this->tarificationLivraisonService = $tarificationLivraisonService;
        $this->geoMatcherService = $geoMatcherService;
    }

    #[Route('', name: 'api_commandes_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getCurrentUser($request, $em, $this->tokenService);
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 20);
        $this->annulerCommandesEnRetard($em);
        $this->annulerEtRembourserNonLivrees(self::DELAI_LIVRAISON_HEURES, $em);
        $this->walletService->autoConfirmerReceptionsEnAttente(self::DELAI_RECEPTION_JOURS);
        if ($user?->getRole() === 'admin') {
            $commandes = $em->getRepository(Commande::class)->findAll();
        } else {
            $commandes = $em->getRepository(Commande::class)->findBy(['acheteur' => $user?->getId() ?? 0]);
        }
        usort($commandes, fn(Commande $a, Commande $b) => $b->getDateCommande() <=> $a->getDateCommande());
        return new JsonResponse(array_slice(array_map(fn(Commande $c) => EntitySerializer::commande($c), $commandes), ($page - 1) * $perPage, $perPage));
    }

    #[Route('/acheteur/{userId}', name: 'api_commandes_by_acheteur', methods: ['GET'])]
    public function byAcheteur(int $userId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        if ($user->getRole() !== 'admin' && $user->getId() !== $userId) {
            return $this->error('Accès refusé.', 403);
        }
        $commandes = $em->getRepository(Commande::class)->findBy(['acheteur' => $userId]);
        return new JsonResponse(array_map(fn(Commande $c) => EntitySerializer::commande($c), $commandes));
    }

    #[Route('/producteur/{userId}', name: 'api_commandes_by_producteur', methods: ['GET'])]
    public function byProducteur(int $userId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        if ($user->getRole() !== 'admin' && $user->getId() !== $userId) {
            return $this->error('Accès refusé.', 403);
        }
        $commandes = $em->createQueryBuilder()
            ->select('c')
            ->from(Commande::class, 'c')
            ->join('c.lignes', 'l')
            ->join('l.lot', 'lot')
            ->where('lot.producteur = :producteurId')
            ->setParameter('producteurId', $userId)
            ->distinct()
            ->getQuery()
            ->getResult();
        return new JsonResponse(array_map(fn(Commande $c) => EntitySerializer::commande($c), $commandes));
    }

    #[Route('/estimation-frais', name: 'api_commandes_estimation_frais', methods: ['POST'])]
    public function estimationFrais(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $lignesData = $data['lignes'] ?? [];
            if (empty($lignesData)) {
                return $this->error('Aucune ligne fournie.', 400);
            }

            $lignes = [];
            foreach ($lignesData as $ligneData) {
                $lot = isset($ligneData['lot_id']) ? $em->getRepository(Lot::class)->find((int) $ligneData['lot_id']) : null;
                if (!$lot instanceof Lot) {
                    return $this->error('Lot non trouvé.', 404);
                }
                $ligne = new LigneCommande();
                $ligne->setLot($lot);
                $lignes[] = $ligne;
            }

            $origine = $this->livraisonGeoService->pointOrigine($lignes);
            if ($origine === null) {
                return $this->error('Position de retrait manquante : le lot sélectionné (ou son producteur) n\'a pas de position définie.', 400);
            }

            $livreurReference = $this->resoudreLivreurReference($data, $origine, $em);
            $tarif = $this->tarificationLivraisonService->calculerFrais($lignes, $user, $livreurReference);
            if ($tarif['frais'] === null) {
                return $this->error('Votre position de livraison n\'est pas définie. La livraison est impossible.', 400);
            }

            return new JsonResponse([
                'frais_livraison' => $tarif['frais'],
                'distance_ramassage_km' => $tarif['distance_ramassage_km'],
                'distance_livraison_km' => $tarif['distance_livraison_km'],
                'total_km' => round($tarif['distance_ramassage_km'] + $tarif['distance_livraison_km'], 2),
                'plafond_depasse' => $tarif['plafond_depasse'],
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}', name: 'api_commandes_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->annulerEtRembourserNonLivrees(self::DELAI_LIVRAISON_HEURES, $em);
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande instanceof Commande) {
            return $this->error('Commande non trouvée.', 404);
        }
        if ($user->getRole() === 'admin' || $commande->getAcheteur()?->getId() === $user->getId() || $this->isProducerForCommande($commande, $user)) {
            return new JsonResponse(EntitySerializer::commande($commande));
        }
        return $this->error('Accès refusé.', 403);
    }

    #[Route('', name: 'api_commandes_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            $lignesData = $data['lignes'] ?? [];
            if (empty($lignesData)) {
                return $this->error('Aucune ligne de commande fournie.', 400);
            }
            $commande = new Commande();
            $commande->setAcheteur($user);
            $mode = $data['mode_recuperation'] ?? ModeRecuperation::LIVRAISON->value;
            if ($mode !== ModeRecuperation::LIVRAISON->value) {
                return $this->error('Le mode de récupération "retrait" n\'est plus proposé : seule la livraison est disponible.', 400);
            }
            $commande->setModeRecuperation($mode);
            $em->persist($commande);

            $montantProduits = 0.0;
            $commissionTotale = 0.0;
            foreach ($lignesData as $ligneData) {
                $lot = isset($ligneData['lot_id']) ? $em->getRepository(Lot::class)->find($ligneData['lot_id']) : null;
                if (!$lot instanceof Lot) {
                    return $this->error('Lot non trouvé.', 404);
                }
                $quantite = (float) ($ligneData['quantite'] ?? 0);
                if ($quantite <= 0) {
                    return $this->error('Quantité invalide.', 400);
                }
                // RG03 : stock disponible au moment de la création de la commande.
                if ($lot->getQuantiteRestante() < $quantite) {
                    return $this->error('Stock insuffisant pour le lot ' . $lot->getId() . '.', 400);
                }
                $categorie = $lot->getProduit()?->getCategorie();
                $prixBase = (float) ($lot->calculerPrixReduit() ?? $lot->getPrixProducteur());
                $commissionUnitaire = $categorie ? $categorie->calculerCommission((float) $lot->getPrixProducteur()) : 0.0;
                $prixUnitaire = $prixBase + $commissionUnitaire;
                $sousTotal = $quantite * $prixUnitaire;
                $montantProduits += $quantite * $prixBase;
                $commissionTotale += $quantite * $commissionUnitaire;
                $ligne = new LigneCommande();
                $ligne->setCommande($commande);
                $ligne->setLot($lot);
                $ligne->setQuantite($quantite);
                $ligne->setPrixUnitaire($prixUnitaire);
                $ligne->setSousTotal($sousTotal);
                $commande->addLigne($ligne);
                $em->persist($ligne);
            }
            $commande->setMontantProduits($montantProduits);
            $commande->setCommission($commissionTotale);

            $origine = $this->livraisonGeoService->pointOrigine($commande->getLignes());
            if ($origine === null) {
                return $this->error('Position de retrait manquante : le lot sélectionné (ou son producteur) n\'a pas de position définie. La livraison est impossible.', 400);
            }
            if ($this->livraisonGeoService->pointDestination($user) === null) {
                return $this->error('Votre position de livraison n\'est pas définie. La livraison est impossible.', 400);
            }
            $livreurReference = $this->resoudreLivreurReference($data, $origine, $em);
            $tarif = $this->tarificationLivraisonService->calculerFrais($commande->getLignes(), $user, $livreurReference);
            if ($tarif['frais'] === null) {
                return $this->error('Impossible de calculer les frais de livraison : positions manquantes.', 400);
            }
            if ($tarif['plafond_depasse']) {
                return $this->error(sprintf('La distance de livraison dépasse le plafond autorisé (%d FCFA). La livraison est refusée.', (int) $this->tarificationLivraisonService->getPlafond()), 400);
            }
            $commande->setFraisLivraison((string) $tarif['frais']);

            $commande->setMontantTotal($montantProduits + $commissionTotale + (float) $commande->getFraisLivraison());
            $em->flush();

            $this->notificationService->notifierEtEnvoyer(
                $user,
                'Commande #' . $commande->getId() . ' confirmée',
                sprintf(
                    "Bonjour %s,\n\nVotre commande #%d a bien été enregistrée.\nMontant total : %s FCFA.\nUn producteur va la confirmer prochainement.\n\nL'équipe TerraLink",
                    $user->getPrenom(),
                    $commande->getId(),
                    number_format((float) $commande->getMontantTotal(), 0, '.', ' ')
                )
            );

            $producteursNotifies = [];
            foreach ($commande->getLignes() as $ligne) {
                $producteur = $ligne->getLot()?->getProducteur();
                if ($producteur === null || isset($producteursNotifies[$producteur->getId()])) {
                    continue;
                }
                $producteursNotifies[$producteur->getId()] = true;
                $this->notificationService->notifierEtEnvoyer(
                    $producteur,
                    'Nouvelle commande reçue',
                    sprintf('Vous avez reçu une nouvelle commande #%d. Connectez-vous pour la confirmer.', $commande->getId())
                );
            }
            $em->flush();

            return new JsonResponse(EntitySerializer::commande($commande), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/confirmer', name: 'api_commandes_confirmer', methods: ['PUT'])]
    public function confirmer(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'producteur');
            $commande = $em->getRepository(Commande::class)->find($id);
            if (!$commande instanceof Commande) {
                return $this->error('Commande non trouvée.', 404);
            }
            if ($commande->estEnRetardDeConfirmation(self::DELAI_CONFIRMATION_HEURES)) {
                $commande->annuler();
                $em->flush();
                return $this->error('RG07 : délai de confirmation dépassé, la commande a été annulée.', 409);
            }
            if (!$this->isProducerForCommande($commande, $user)) {
                return $this->error('Vous n\'êtes pas le producteur concerné par cette commande.', 403);
            }
            $commande->confirmer();
            $em->flush();
            return new JsonResponse(EntitySerializer::commande($commande));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/annuler', name: 'api_commandes_annuler', methods: ['PUT'])]
    public function annuler(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $commande = $em->getRepository(Commande::class)->find($id);
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
            $commande->annuler();
            $em->flush();
            return new JsonResponse(EntitySerializer::commande($commande));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/litige', name: 'api_commandes_litige_create', methods: ['POST'])]
    public function creerLitige(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $commande = $em->getRepository(Commande::class)->find($id);
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
            $data = json_decode($request->getContent(), true) ?? [];
            if (empty($data['motif'])) {
                return $this->error('Le motif du litige est requis.', 400);
            }
            $litige = new Litige();
            $litige->setCommande($commande);
            $litige->setMotif($data['motif']);
            $litige->setDescription($data['description'] ?? null);
            $em->persist($litige);
            $em->flush();
            return new JsonResponse(EntitySerializer::litige($litige, $em), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Partie 9 — L'acheteur confirme avoir bien reçu sa commande.
     * Restreint à l'acheteur de la commande. Déclenche le crédit du livreur
     * et des producteurs (qui était auparavant fait à la livraison).
     */
    #[Route('/{id}/confirmer-reception', name: 'api_commandes_confirmer_reception', methods: ['PUT'])]
    public function confirmerReception(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $commande = $em->getRepository(Commande::class)->find($id);
            if (!$commande instanceof Commande) {
                return $this->error('Commande non trouvée.', 404);
            }
            if ($commande->getAcheteur()?->getId() !== $user->getId()) {
                return $this->error('Seul l\'acheteur de cette commande peut confirmer la réception.', 403);
            }
            $livraison = $em->getRepository(Livraison::class)->findOneByCommande($id);
            if (!$livraison instanceof Livraison) {
                return $this->error('Aucune livraison associée à cette commande.', 400);
            }
            if ($livraison->getStatut() !== StatutLivraison::LIVREE->value) {
                return $this->error('La livraison doit d\'abord être marquée comme livrée.', 400);
            }
            if ($livraison->isReceptionConfirmee()) {
                return $this->error('La réception a déjà été confirmée.', 400);
            }
            $livraison->confirmerReception();
            $commande->setStatut(StatutCommande::LIVREE->value);
            $em->flush();
            $this->walletService->crediterLivraison($livraison);
            return new JsonResponse(EntitySerializer::livraison($livraison));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    private function annulerCommandesEnRetard(EntityManagerInterface $em): void
    {
        $commandes = $em->getRepository(Commande::class)->findBy(['statut' => StatutCommande::EN_ATTENTE->value]);
        foreach ($commandes as $commande) {
            if ($commande->estEnRetardDeConfirmation(self::DELAI_CONFIRMATION_HEURES)) {
                $commande->annuler();
            }
        }
        $em->flush();
    }

    /**
     * RG : si la commande payée n'est pas livrée dans le délai imparti (24 h),
     * la commande est annulée (stock libéré), la livraison est marquée échouée
     * et le montant payé est recrédité sur le portefeuille de l'acheteur.
     * Idempotent : uniquement les commandes encore payées/en cours, et le crédit
     * est protégé par une référence de transaction unique.
     *
     * @return int nombre de commandes annulées et remboursées
     */
    private function annulerEtRembourserNonLivrees(int $delaiHeures, EntityManagerInterface $em): int
    {
        $seuil = (new \DateTime())->modify("-{$delaiHeures} hours");
        $commandes = $em->createQueryBuilder()
            ->select('c')
            ->from(Commande::class, 'c')
            ->where('c.statut IN (:statuts)')
            ->setParameter('statuts', [StatutCommande::PAYEE->value, StatutCommande::EN_COURS->value])
            ->andWhere('c.dateCommande <= :seuil')
            ->setParameter('seuil', $seuil)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($commandes as $commande) {
            if (!$commande instanceof Commande || $this->hasLitigeOuvert($commande, $em)) {
                continue;
            }
            $livraison = $em->getRepository(Livraison::class)->findOneBy(['commande' => $commande->getId()]);
            $paiement = $em->getRepository(Paiement::class)->findOneBy(['commande' => $commande->getId()]);

            $commande->annuler();
            if ($livraison instanceof Livraison) {
                $livraison->setStatut(StatutLivraison::ECHOUEE->value);
            }

            if ($paiement instanceof Paiement && $paiement->getStatut() === StatutPaiement::VALIDE->value) {
                $refRemb = 'remboursement-commande-' . $commande->getId();
                if (!$em->getRepository(Transaction::class)->findByReference($refRemb)) {
                    $acheteur = $commande->getAcheteur();
                    if ($acheteur instanceof User) {
                        $paiement->rembourser();
                        $this->walletService->credit(
                            $acheteur,
                            number_format((float) $commande->getMontantTotal(), 2, '.', ''),
                            'remboursement_commande_non_livree',
                            $refRemb
                        );
                    }
                }
            }
            $em->flush();
            $count++;
        }
        return $count;
    }

    private function hasLitigeOuvert(Commande $commande, EntityManagerInterface $em): bool
    {
        $litiges = $em->getRepository(Litige::class)->findBy(['commande' => $commande]);
        foreach ($litiges as $litige) {
            if (in_array($litige->getStatut(), [\App\Enum\StatutLitige::OUVERT->value, \App\Enum\StatutLitige::EN_TRAITEMENT->value], true)) {
                return true;
            }
        }
        return false;
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

    /**
     * Référence du trajet de ramassage pour la tarification : livreur choisi au
     * checkout (livreur_id positionné) si fourni et positionné, sinon meilleur
     * livreur disponible le plus proche, sinon null (trajet de ramassage = 0 km).
     */
    private function resoudreLivreurReference(array $data, array $origine, EntityManagerInterface $em): ?Livreur
    {
        if (!empty($data['livreur_id'])) {
            $candidate = $em->getRepository(Livreur::class)->find((int) $data['livreur_id']);
            if ($candidate instanceof Livreur && $candidate->getLatitude() && $candidate->getLongitude()) {
                return $candidate;
            }
        }

        $match = $this->geoMatcherService->trouverMeilleurLivreur((float) $origine['lat'], (float) $origine['lng']);

        return $match['livreur'] ?? null;
    }
}
