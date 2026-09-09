<?php

namespace App\Service;

use App\Entity\Acheteur;
use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Paiement;
use App\Enum\StatutCommande;
use App\Enum\StatutPaiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Orchestration des commandes : création, décrémentation du stock,
 * commission, transition de statut et emails transactionnels.
 */
class CommandeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockService $stockService,
        private readonly CommissionService $commissionService,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @param array<int, array{produitId:int, couleur:string, taille:string, quantite:int}> $items
     *
     * @throws \DomainException si le stock est insuffisant ou le panier vide.
     */
    public function passerCommande(Acheteur $acheteur, Boutique $boutique, array $items, bool $payerMaintenant = true): Commande
    {
        if (count($items) === 0) {
            throw new \DomainException('Le panier est vide.');
        }

        $commande = new Commande();
        $commande->setAcheteur($acheteur);
        $commande->setBoutique($boutique);
        $commande->setStatut(StatutCommande::EN_ATTENTE);

        $montantProduits = 0.0;
        foreach ($items as $item) {
            $produit = $this->entityManager->getRepository(\App\Entity\Produit::class)->find($item['produitId']);
            if (!$produit instanceof \App\Entity\Produit) {
                throw new \DomainException('Produit introuvable.');
            }
            if (!$produit->isActif()) {
                throw new \DomainException(sprintf('Le produit "%s" n\'est plus disponible.', $produit->getNom()));
            }

            $variante = $this->trouverVariante($produit, $item['couleur'], $item['taille']);
            $prixUnitaire = $produit->getPrixActuel();

            $ligne = new LigneCommande();
            $ligne->setProduit($produit);
            $ligne->setCouleur($item['couleur']);
            $ligne->setTaille($item['taille']);
            $ligne->setPrixUnitaire($prixUnitaire);
            $ligne->setQuantite($item['quantite']);

            // Validation + réservation du stock AVANT d'enregistrer.
            $variante->vendre($item['quantite']);
            $commande->addLigne($ligne);
            $montantProduits += $ligne->getSousTotal();
        }

        $commande->setMontantProduits($montantProduits);
        $commande->setMontantTotal($montantProduits);
        $commande->setCommission($this->commissionService->calculerCommission($montantProduits));

        $this->entityManager->persist($commande);

        if ($payerMaintenant) {
            $paiement = new Paiement();
            $paiement->setCommande($commande);
            $paiement->setMontant($commande->getMontantTotal());
            $paiement->setTauxCommission(CommissionService::TAUX_COMMISSION);
            $paiement->setCommission($commande->getCommission());
            $paiement->setStatut(StatutPaiement::VALIDE);
            $this->entityManager->persist($paiement);
            $commande->setPaiement($paiement);
        }

        $this->entityManager->flush();

        $this->envoyerEmailConfirmation($commande);

        return $commande;
    }

    public function changerStatut(Commande $commande, StatutCommande $statut): Commande
    {
        $commande->setStatut($statut);
        $this->entityManager->flush();

        if ($statut === StatutCommande::LIVREE) {
            $this->envoyerEmailRappelAvis($commande);
        }

        return $commande;
    }

    private function trouverVariante(\App\Entity\Produit $produit, string $couleur, string $taille): \App\Entity\Variante
    {
        foreach ($produit->getVariantes() as $variante) {
            if ($variante->getCouleur() === $couleur && $variante->getTaille() === $taille) {
                return $variante;
            }
        }
        throw new \DomainException(sprintf(
            'Variante introuvable (%s / %s) pour le produit "%s".',
            $couleur,
            $taille,
            $produit->getNom(),
        ));
    }

    private function envoyerEmailConfirmation(Commande $commande): void
    {
        $acheteur = $commande->getAcheteur();
        if ($acheteur?->getEmail() === null) {
            return;
        }
        $email = (new TemplatedEmail())
            ->from('no-reply@gmarket.app')
            ->to($acheteur->getEmail())
            ->subject('Confirmation de votre commande #' . $commande->getId())
            ->htmlTemplate('emails/confirmation_commande.html.twig')
            ->context([
                'commande' => $commande,
                'acheteur' => $acheteur,
            ]);
        $this->mailer->send($email);
    }

    private function envoyerEmailRappelAvis(Commande $commande): void
    {
        $acheteur = $commande->getAcheteur();
        if ($acheteur?->getEmail() === null) {
            return;
        }
        $email = (new TemplatedEmail())
            ->from('no-reply@gmarket.app')
            ->to($acheteur->getEmail())
            ->subject('Votre commande a été livrée — laissez un avis !')
            ->htmlTemplate('emails/rappel_avis.html.twig')
            ->context([
                'commande' => $commande,
                'acheteur' => $acheteur,
            ]);
        $this->mailer->send($email);
    }
}