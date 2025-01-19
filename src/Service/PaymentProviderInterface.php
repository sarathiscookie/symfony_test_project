<?php

namespace App\Service;

interface PaymentProviderInterface
{
    /**
     * Processes a payment for the given provider.
     *
     * @param array $params Payment details.
     * @return array Processed payment response.
     */
    public function processPayment(array $params): array;
}
