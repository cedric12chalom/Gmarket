<?php

namespace App\Command;

use App\Entity\HistoriquePrix;
use App\Entity\Lot;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:prix:releve-quotidien', description: 'Calcule le prix moyen quotidien de chaque produit actif.')]
class PrixReleveCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime('today');
        $produits = $this->em->getRepository(Produit::class)->findAll();
        $count = 0;

        foreach ($produits as $produit) {
            $existing = $this->em->getRepository(HistoriquePrix::class)->findByProduitAndDate($produit->getId(), $today);
            if ($existing) continue;

            $lots = $this->em->createQueryBuilder()
                ->select('l')
                ->from(Lot::class, 'l')
                ->andWhere('l.produit = :produit')
                ->andWhere('l.statut = :statut')
                ->andWhere('l.quantiteDisponible > 0')
                ->setParameter('produit', $produit->getId())
                ->setParameter('statut', \App\Enum\StatutLot::DISPONIBLE->value)
                ->getQuery()
                ->getResult();

            if (empty($lots)) continue;

            $total = 0;
            foreach ($lots as $lot) {
                $total += (float) $lot->getPrixProducteur();
            }
            $moyenne = $total / count($lots);

            $hp = new HistoriquePrix();
            $hp->setProduit($produit);
            $hp->setPrixMoyen((string) round($moyenne, 2));
            $hp->setDateReleve($today);
            $this->em->persist($hp);
            $count++;
        }

        $this->em->flush();
        $output->writeln(sprintf('Historique enregistré pour %d produit(s).', $count));
        return Command::SUCCESS;
    }
}