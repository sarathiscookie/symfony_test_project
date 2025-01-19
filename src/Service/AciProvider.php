<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;

class AciProvider implements PaymentProviderInterface
{
    private const PAYMENTS_URL = 'https://eu-test.oppwa.com/v1/payments';
    private const BEARER_TOKEN = 'OGFjN2E0Yzc5Mzk0YmRjODAxOTM5NzM2ZjFhNzA2NDF8Ulh5az9pd2ZNdXprRVpRYjdFcWs=';
    private const ENTITY_ID = '8ac7a4c79394bdc801939736f17e063d';
    private const PAYMENT_TYPE = 'DB';
    private const CARD_HOLDER = 'Jane Jones';

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Processes a payment using ACI.
     *
     * @param array $params Payment details (amount, currency, card details).
     * @return array Processed payment response.
     * @throws \RuntimeException If there is an error during payment processing.
     */
    public function processPayment(array $params): array
    {
        // Validate parameters
        $this->validateParams($params, [
            'amount',
            'currency',
            'card.number',
            'card.expiryMonth',
            'card.expiryYear',
            'card.cvv'
        ]);

        // Build JSON request payload
        $jsonPayload = [
            'entityId' => self::ENTITY_ID,
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'paymentType' => self::PAYMENT_TYPE,
            'card.number' => $params['card.number'],
            'card.holder' => self::CARD_HOLDER,
            'card.expiryMonth' => $params['card.expiryMonth'],
            'card.expiryYear' => $params['card.expiryYear'],
            'card.cvv' => $params['card.cvv'],
        ];

        // Convert the array to x-www-form-urlencoded format
        $encodedFormData = http_build_query($jsonPayload);

        try {
            // Make the API request
            $response = $this->httpClient->request('POST', self::PAYMENTS_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::BEARER_TOKEN,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $encodedFormData,
            ]);

            $data = $response->toArray();

            // Format and return the unified response
            return $this->formatResponse(
                $data['id'] ?? null,
                $data['timestamp'] ?? null,
                $data['amount'],
                $data['currency'],
                $data['card']['bin']
            );
        } catch (TransportExceptionInterface | DecodingExceptionInterface $e) {
            throw new \RuntimeException('Error occurred during ACI payment: ' . $e->getMessage());
        }
    }

    /**
     * Validates the required fields in the parameters.
     *
     * @param array $params Parameters to validate.
     * @param array $requiredFields Required fields to check for.
     * @throws \InvalidArgumentException If a required field is missing.
     */
    private function validateParams(array $params, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }

    /**
     * Formats the response data for consistency.
     *
     * @param string|null $transactionId Transaction ID.
     * @param string|null $created Creation timestamp.
     * @param string $amount Payment amount.
     * @param string $currency Currency type.
     * @param string $bin Card BIN.
     * @return array Formatted response data.
     */
    private function formatResponse($transactionId, $created, $amount, $currency, $bin): array
    {
        return [
            'transactionId' => $transactionId,
            'created' => $this->formatDateAndTime($created),
            'amount' => $amount,
            'currency' => $currency,
            'cardBin' => $bin,
        ];
    }

    /**
     * Formats date and time from a timestamp or string.
     *
     * @param mixed $input The timestamp or datetime string.
     * @return string The formatted date and time.
     */
    private function formatDateAndTime($input)
    {
        // Check if the input is a valid timestamp
        if (is_numeric($input)) {
            // If it's a timestamp, convert it to a DateTime object
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($input);
        } else {
            // Otherwise, treat the input as a datetime string
            try {
                $dateTime = new \DateTime($input);
            } catch (\Exception $e) {
                // Return an error if the input is not valid
                return "Invalid input format";
            }
        }

        // Format the DateTime object as d.m.y H:i:s
        return $dateTime->format("d.m.y H:i:s");
    }
}
