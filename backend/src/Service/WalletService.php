<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Litige;
use App\Entity\Livraison;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\StatutLitige;
use App\Enum\StatutLivraison;
use Doctrine\ORM\EntityManagerInterface;

class WalletService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Crédite le livreur (frais) et les producteurs (revenus net) d'une livraison.
     * Idempotent via les références de transaction.
     */
    public function crediterLivraison(Livraison $livraison): void
    {
        $commande = $livraison->getCommande();
        $livreur = $livraison->getLivreur();

        if ($commande && $livreur) {
            $fraisRef = 'livraison-' . $livraison->getId();
            if (!$this->em->getRepository(Transaction::class)->findByReference($fraisRef)) {
                $this->credit($livreur, (string) $livraison->getFrais(), 'frais_livraison_gagne', $fraisRef);
            }
            foreach ($commande->getLignes() as $ligne) {
                $producteur = $ligne->getLot()?->getProducteur();
                if (!$producteur) continue;
                $produit = $ligne->getLot()?->getProduit();
                $categorie = $produit?->getCategorie();
                $commission = $categorie ? $categorie->calculerCommission((float) $ligne->getLot()->getPrixProducteur()) : 0.0;
                $montantProducteur = ((float) $ligne->getLot()->getPrixProducteur()) * $ligne->getQuantite();
                $montantNet = $montantProducteur - ($commission * $ligne->getQuantite());
                $venteRef = 'vente-' . $ligne->getId() . '-' . $livraison->getId();
                if (!$this->em->getRepository(Transaction::class)->findByReference($venteRef)) {
                    $this->credit($producteur, (string) $montantNet, 'revenu_vente', $venteRef);
                }
            }
        }
    }

    /**
     * Finalise automatiquement la réception des livraisons livrées mais non confirmées
     * après le délai de grâce (jours). Saute les commandes faisant l'objet d'un litige
     * non résolu : celles-ci restent en attente de décision d'un admin.
     *
     * @return int nombre de livraisons finalisées
     */
    public function autoConfirmerReceptionsEnAttente(int $delaiJours): int
    {
        $seuil = (new \DateTime())->modify("-{$delaiJours} days");
        $repo = $this->em->getRepository(Livraison::class);
        $query = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Livraison::class, 'l')
            ->where('l.statut = :statut')
            ->andWhere('l.dateReceptionConfirmee IS NULL')
            ->andWhere('l.dateLivraison <= :seuil')
            ->setParameter('statut', StatutLivraison::LIVREE->value)
            ->setParameter('seuil', $seuil)
            ->getQuery();

        $count = 0;
        foreach ($query->getResult() as $livraison) {
            $commande = $livraison->getCommande();
            if ($commande && $this->hasLitigeOuvert($commande)) {
                continue;
            }
            try {
                $livraison->confirmerReception();
                $commande = $livraison->getCommande();
                if ($commande !== null) {
                    $commande->setStatut(\App\Enum\StatutCommande::LIVREE->value);
                }
                $this->em->flush();
                $this->crediterLivraison($livraison);
                $count++;
            } catch (\Throwable) {
                // déjà confirmée ou état incohérent : on ignore.
            }
        }
        return $count;
    }

    private function hasLitigeOuvert(Commande $commande): bool
    {
        $litiges = $this->em->getRepository(Litige::class)->findBy(['commande' => $commande]);
        foreach ($litiges as $litige) {
            if (in_array($litige->getStatut(), [StatutLitige::OUVERT->value, StatutLitige::EN_TRAITEMENT->value], true)) {
                return true;
            }
        }
        return false;
    }

    public function credit(User $user, string $montant, string $motif, ?string $reference = null): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->em->lock($user, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($user);
            $nouveauSolde = bcadd($user->getSolde() ?? '0.00', $montant, 2);
            $user->setSolde($nouveauSolde);
            $tx = new Transaction();
            $tx->setUtilisateur($user);
            $tx->setType('credit');
            $tx->setMontant($montant);
            $tx->setMotif($motif);
            $tx->setReference($reference);
            $tx->setSoldeApres($nouveauSolde);
            $this->em->persist($tx);
            $this->em->flush();
            $this->em->getConnection()->commit();
            return $tx;
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    public function debit(User $user, string $montant, string $motif, ?string $reference = null, bool $manageTransaction = true): Transaction
    {
        if ($manageTransaction) {
            $this->em->getConnection()->beginTransaction();
        }
        try {
            $this->em->lock($user, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($user);
            $soldeActuel = $user->getSolde() ?? '0.00';
            if (bccomp($soldeActuel, $montant, 2) < 0) {
                throw new \RuntimeException('Solde insuffisant.');
            }
            $nouveauSolde = bcsub($soldeActuel, $montant, 2);
            $user->setSolde($nouveauSolde);
            $tx = new Transaction();
            $tx->setUtilisateur($user);
            $tx->setType('debit');
            $tx->setMontant($montant);
            $tx->setMotif($motif);
            $tx->setReference($reference);
            $tx->setSoldeApres($nouveauSolde);
            $this->em->persist($tx);
            $this->em->flush();
            if ($manageTransaction) {
                $this->em->getConnection()->commit();
            }
            return $tx;
        } catch (\Throwable $e) {
            if ($manageTransaction) {
                $this->em->getConnection()->rollBack();
            }
            throw $e;
        }
    }
}