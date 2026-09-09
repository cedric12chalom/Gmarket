<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Centralise les notifications en base (clochette) et l'envoi d'emails réels.
 * L'envoi d'email est fait en arrière-plan (processus détaché) pour ne jamais
 * bloquer ni faire échouer la requête HTTP ; un échec n'affecte pas l'API.
 */
class NotificationService
{
    private const FROM_EMAIL = 'kaborecedric2006@gmail.com';
    private const FROM_NAME = 'TerraLink';

    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function notifier(User $user, string $titre, string $message): Notification
    {
        $notification = new Notification();
        $notification->setUtilisateur($user)
            ->setTitre($titre)
            ->setMessage($message);
        $this->em->persist($notification);
        return $notification;
    }

    public function envoyerEmail(string $destinataire, string $objet, string $message): void
    {
        // Envoi asynchrone via un processus détaché : ne bloque jamais la requête HTTP
        // (l'envoi SMTP synchrone faisait durer / échouer la requête à ~60s côté Render).
        try {
            $root = dirname(__DIR__);
            $cmd = sprintf(
                '%s %s/console app:send-email %s %s %s',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($root),
                escapeshellarg($destinataire),
                escapeshellarg($this->b64url($objet)),
                escapeshellarg($this->b64url($message))
            );
            if (DIRECTORY_SEPARATOR === '\\') {
                // Windows : exécution détachée sans bloquer.
                $process = proc_open($cmd, [['pipe', 'r'], ['file', 'NUL', 'w'], ['file', 'NUL', 'w']], $pipes);
                if (is_resource($process)) {
                    proc_close($process);
                }
            } else {
                // Unix (Render) : arrière-plan totalement détaché, pas d'attente.
                $cmd .= ' > /dev/null 2>&1 &';
                exec($cmd);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Impossible de lancer l\'envoi d\'email asynchrone à ' . $destinataire . ' — ' . $e->getMessage());
        }
    }

    private function b64url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public function notifierEtEnvoyer(User $user, string $titre, string $message, ?string $objet = null): void
    {
        $this->notifier($user, $titre, $message);
        $email = $user->getEmail();
        if ($email) {
            $this->envoyerEmail($email, $objet ?? $titre, $message);
        }
    }
}
