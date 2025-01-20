<?php

namespace App\Tests\Service;

use App\Service\AciProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AciProviderTest extends TestCase
{
    private AciProvider $aciProvider;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->aciProvider = new AciProvider($this->httpClient);
    }

    public function testProcessPaymentSuccess()
    {
        $responseData = [
            'id' => 'payment123',
            'timestamp' => '2025-01-01T12:00:00Z',
            'amount' => '92.00',
            'currency' => 'EUR',
            'card' => ['bin' => '411111']
        ];

        $this->httpClient->setResponseFactory(function () use ($responseData) {
            return new MockResponse(json_encode($responseData));
        });

        $params = [
            'amount' => '92.00',
            'currency' => 'USD',
            'card.number' => '4111111111111111',
            'card.expiryMonth' => '12',
            'card.expiryYear' => '2025',
            'card.cvv' => '123'
        ];

        $response = $this->aciProvider->processPayment($params);

        $this->assertArrayHasKey('transactionId', $response);
        $this->assertEquals('payment123', $response['transactionId']);
        $this->assertEquals('01.01.25 12:00:00', $response['created']);
        $this->assertEquals('92.00', $response['amount']);
        $this->assertEquals('EUR', $response['currency']);
        $this->assertEquals('411111', $response['cardBin']);
    }

    public function testProcessPaymentFailure()
    {
        $this->httpClient->setResponseFactory(function () {
            return new MockResponse('', ['http_code' => 500]);
        });

        $params = [
            'amount' => '92.00',
            'currency' => 'USD',
            'card.number' => '4111111111111111',
            'card.expiryMonth' => '12',
            'card.expiryYear' => '2025',
            'card.cvv' => '123'
        ];

        $this->expectException(\RuntimeException::class);
        $this->aciProvider->processPayment($params);
    }

    public function testValidateParamsMissingField()
    {
        $params = [
            'amount' => '92.00',
            'currency' => 'USD'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->aciProvider->processPayment($params);
    }
}
