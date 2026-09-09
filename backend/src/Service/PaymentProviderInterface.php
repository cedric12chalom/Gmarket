<?php

namespace App\Service;

interface PaymentProviderInterface
{
    public function collect(float $montant, string $numero, string $description, string $externalReference): array;

    public function getTransactionStatus(string $reference): array;

    public function isSandbox(): bool;
}
