<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\AbonnementSouscription;
use App\Entity\Vendeur;
use App\Enum\StatutAbonnement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Gestion du cycle de vie des abonnements vendeurs : période d'essai
 * gratuite, passage en formule payante, expiration, emails de rappel.
 */
class AbonnementService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Ouvre un abonnement. Appelé à l'inscription vendeur : la première
     * souscription est la période d'essai gratuite (Abonnement::ESSAI_JOURS),
     * les suivantes sont des formules payantes.
     */
    public function souscrire(Vendeur $vendeur, Abonnement $abonnement, int $dureeJours, StatutAbonnement $statut): AbonnementSouscription
    {
        $now = new \DateTimeImmutable();
        $souscription = new AbonnementSouscription();
        $souscription->setVendeur($vendeur);
        $souscription->setAbonnement($abonnement);
        $souscription->setDateDebut($now);
        $souscription->setDateFin($now->modify(sprintf('+%d days', $dureeJours)));
        $souscription->setStatut($statut);

        $this->entityManager->persist($souscription);
        $this->entityManager->flush();

        return $souscription;
    }

    /**
     * Le vendeur peut-il encore publier/vendre ? Essai en cours ou abonnement
     * payant actif.
     */
    public function peutVendre(Vendeur $vendeur): bool
    {
        return $vendeur->abonnementActuel() !== null;
    }

    /**
     * Détecte les abonnements en cours d'expiration (J+3 avant la fin) pour
     * envoyer un rappel.
     *
     * @return array<int, AbonnementSouscription>
     */
    public function trouverAbonnementsAExpirer(int $avantJours = 3): array
    {
        $now = new \DateTimeImmutable();
        $seuil = $now->modify(sprintf('+%d days', $avantJours));

        return $this->entityManager
            ->getRepository(AbonnementSouscription::class)
            ->createQueryBuilder('a')
            ->where('a.dateFin BETWEEN :now AND :seuil')
            ->setParameter('now', $now)
            ->setParameter('seuil', $seuil)
            ->getQuery()
            ->getResult();
    }

    public function envoyerRappelFinEssai(AbonnementSouscription $souscription): void
    {
        $vendeur = $souscription->getVendeur();
        if ($vendeur?->getEmail() === null) {
            return;
        }
        $email = (new TemplatedEmail())
            ->from('no-reply@gmarket.app')
            ->to($vendeur->getEmail())
            ->subject('Votre période d\'essai se termine bientôt')
            ->htmlTemplate('emails/fin_essai.html.twig')
            ->context([
                'vendeur' => $vendeur,
                'souscription' => $souscription,
            ]);
        $this->mailer->send($email);
    }
}