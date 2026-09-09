<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:db:reset-demo',
    description: 'Purge toutes les tables métier puis recharge les données de démonstration complètes (catalogue, comptes de démo, lots, livraisons).'
)]
class ResetDemoCommand extends Command
{
    /**
     * Ordre de suppression respectant les contraintes de clé étrangère :
     * tables feuilles d'abord, utilisateurs en dernier.
     */
    private const TABLES_IN_DELETE_ORDER = [
        'notification',
        'message',
        'transaction',
        'avis',
        'historique_prix',
        'litige',
        'ligne_commande',
        'livraison',
        'paiement',
        'geo_track',
        'commande',
        'lot',
        'variete',
        'produit',
        'categorie',
        '`user`',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande vide les tables métier dans le bon ordre (FK), puis exécute app:seed:catalogue et app:seed:geo pour reconstruire une base de démonstration complète et valide.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Purge des tables métier...</info>');
        $connection = $this->entityManager->getConnection();

        foreach (self::TABLES_IN_DELETE_ORDER as $table) {
            $connection->executeStatement(sprintf('DELETE FROM %s', $table));
        }

        $output->writeln(sprintf('<info>Tables vidées : %d.</info>', count(self::TABLES_IN_DELETE_ORDER)));

        $output->writeln('<info>Seed du catalogue (catégories, produits, variétés)...</info>');
        $this->runSeedCommand('app:seed:catalogue', $output);

        $output->writeln('<info>Seed des données de démonstration (comptes, lots, livraisons, carte)...</info>');
        $this->runSeedCommand('app:seed:geo', $output);

        $output->writeln('<comment>Base de démonstration réinitialisée. Compte admin : admin@terralink.cm / ' . SeedGeoCommand::DEMO_PASSWORD . '</comment>');

        return Command::SUCCESS;
    }

    private function runSeedCommand(string $name, OutputInterface $output): void
    {
        $application = $this->getApplication();
        if (!$application) {
            throw new \RuntimeException(sprintf('Impossible de récupérer l\'application console pour lancer %s.', $name));
        }
        $command = $application->find($name);
        $command->run(new ArrayInput([]), $output);
    }
}
