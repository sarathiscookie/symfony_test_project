<?php

namespace App\Service;

use App\Service\Shift4Provider;
use App\Service\AciProvider;
use InvalidArgumentException;

class PaymentService
{
    private Shift4Provider $shift4Provider;
    private AciProvider $aciProvider;

    public function __construct(
        Shift4Provider $shift4Provider,
        AciProvider $aciProvider
    ) {
        $this->shift4Provider = $shift4Provider;
        $this->aciProvider = $aciProvider;
    }

    /**
     * Processes a payment using the specified provider.
     *
     * @param string $provider The payment provider ('aci' or 'shift4').
     * @param array $params The payment parameters (amount, currency, card details, etc.).
     * @return array The processed payment response.
     * @throws InvalidArgumentException If the provider is invalid.
     */
    public function processPayment(string $provider, array $params): array
    {
        if ($provider === 'shift4') {
            return $this->shift4Provider->processPayment($params);
        } elseif ($provider === 'aci') {
            return $this->aciProvider->processPayment($params);
        } else {
            throw new InvalidArgumentException('Invalid payment provider.');
        }
    }
}
