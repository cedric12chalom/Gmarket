<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Envoi un email seul, exécuté en tâche de fond (processus détaché) par
 * NotificationService::envoyerEmail() pour ne jamais bloquer la requête HTTP.
 */
#[AsCommand(name: 'app:send-email', description: 'Envoie un email (utilisé en arrière-plan pour ne pas bloquer l\'API).')]
class SendEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('to', InputArgument::REQUIRED, 'Destinataire (email)')
            ->addArgument('subject', InputArgument::REQUIRED, 'Objet (base64url)')
            ->addArgument('body', InputArgument::REQUIRED, 'Corps (base64url)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = (string) $input->getArgument('to');
        $subject = $this->b64decode((string) $input->getArgument('subject'));
        $body = $this->b64decode((string) $input->getArgument('body'));

        try {
            $email = (new Email())
                ->from('TerraLink <kaborecedric2006@gmail.com>')
                ->to($to)
                ->subject($subject)
                ->html('<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . '</p>');
            $this->mailer->send($email);
            $output->writeln(sprintf('Email envoyé à %s.', $to));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error('Échec envoi email asynchrone à ' . $to . ' — ' . $e->getMessage());
            $output->writeln('Échec envoi email : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function b64decode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'), true) ?: '';
    }
}
