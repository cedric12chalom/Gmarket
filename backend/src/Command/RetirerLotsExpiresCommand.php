<?php

namespace App\Command;

use App\Service\LotLifecycleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:lots:retirer-expires', description: 'Retire automatiquement du catalogue les lots périmés depuis plus d\'un jour (règle CGU).')]
class RetirerLotsExpiresCommand extends Command
{
    public function __construct(private LotLifecycleService $lotLifecycleService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->lotLifecycleService->retirerLotsExpires();
        $output->writeln(sprintf('%d lot(s) périmé(s) retiré(s) du catalogue.', $count));
        return Command::SUCCESS;
    }
}
