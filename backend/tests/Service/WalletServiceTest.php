<?php

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\WalletService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WalletServiceTest extends TestCase
{
    public function testDebitCanReuseExistingTransaction(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('beginTransaction');
        $connection->expects($this->never())->method('commit');
        $connection->expects($this->never())->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getConnection');
        $em->expects($this->once())
            ->method('lock')
            ->with($this->isInstanceOf(User::class), LockMode::PESSIMISTIC_WRITE);
        $em->expects($this->once())
            ->method('refresh')
            ->with($this->isInstanceOf(User::class));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Transaction::class));
        $em->expects($this->once())->method('flush');

        $user = new User();
        $user->setSolde('500.00');

        $service = new WalletService($em);
        $transaction = $service->debit($user, '100.00', 'paiement commande', 'ref-1', false);

        $this->assertSame('debit', $transaction->getType());
        $this->assertSame('400.00', $user->getSolde());
    }
}
