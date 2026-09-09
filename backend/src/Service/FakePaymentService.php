<?php

namespace App\Service;

class FakePaymentService implements PaymentProviderInterface
{
    public const STATUT_SUCCESSFUL = 'SUCCESSFUL';
    public const STATUT_FAILED = 'FAILED';
    public const STATUT_PENDING = 'PENDING';

    public function isSandbox(): bool
    {
        return true;
    }

    public function collect(float $montant, string $numero, string $description, string $externalReference): array
    {
        $reference = 'FAKE-' . $externalReference;
        $status = $this->determineInitialStatus($numero);

        return [
            'reference' => $reference,
            'status' => $status,
            'amount' => $montant,
            'operator' => 'fake',
        ];
    }

    public function getTransactionStatus(string $reference): array
    {
        if (!str_starts_with($reference, 'FAKE-')) {
            return ['reference' => $reference, 'status' => self::STATUT_FAILED, 'amount' => 0];
        }

        $externalRef = substr($reference, 5);
        $parts = explode('-', $externalRef);
        $montant = (float) ($parts[2] ?? 0);

        return [
            'reference' => $reference,
            'status' => self::STATUT_SUCCESSFUL,
            'amount' => $montant,
        ];
    }

    private function determineInitialStatus(string $numero): string
    {
        $digits = preg_replace('/\D/', '', $numero) ?? '';
        if (str_ends_with($digits, '9')) {
            return self::STATUT_FAILED;
        }
        return self::STATUT_SUCCESSFUL;
    }
}
