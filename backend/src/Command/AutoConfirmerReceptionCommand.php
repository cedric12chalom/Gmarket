<?php

namespace App\Command;

use App\Service\WalletService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:livraisons:auto-confirmer-reception', description: 'Finalise automatiquement la réception des livraisons livrées non confirmées après le délai de grâce.')]
class AutoConfirmerReceptionCommand extends Command
{
    private const DELAI_RECEPTION_JOURS = 7;

    public function __construct(private WalletService $walletService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->walletService->autoConfirmerReceptionsEnAttente(self::DELAI_RECEPTION_JOURS);
        $output->writeln(sprintf('Réception finalisée pour %d livraison(s).', $count));
        return Command::SUCCESS;
    }
}
