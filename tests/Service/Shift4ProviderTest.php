<?php

namespace App\Tests\Service;

use App\Service\Shift4Provider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class Shift4ProviderTest extends TestCase
{
    private Shift4Provider $shift4Provider;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->shift4Provider = new Shift4Provider($this->httpClient);
    }

    public function testProcessPaymentSuccess()
    {
        $responseData = [
            'id' => 'transaction123',
            'created' => '2025-01-01T12:00:00Z',
            'amount' => '490',
            'currency' => 'USD',
            'card' => ['first6' => '424242']
        ];

        $this->httpClient->setResponseFactory(function () use ($responseData) {
            return new MockResponse(json_encode($responseData));
        });

        $params = [
            'amount' => '490',
            'currency' => 'USD',
            'number' => '4242424242424242',
            'expMonth' => '11',
            'expYear' => '2028',
            'cvc' => '123'
        ];

        $response = $this->shift4Provider->processPayment($params);

        $this->assertArrayHasKey('transactionId', $response);
        $this->assertEquals('transaction123', $response['transactionId']);
        $this->assertEquals('01.01.25 12:00:00', $response['created']);
        $this->assertEquals('490', $response['amount']);
        $this->assertEquals('USD', $response['currency']);
        $this->assertEquals('424242', $response['cardBin']);
    }

    public function testProcessPaymentFailure()
    {
        $this->httpClient->setResponseFactory(function () {
            return new MockResponse('', ['http_code' => 500]);
        });

        $params = [
            'amount' => '490',
            'currency' => 'USD',
            'number' => '4242424242424242',
            'expMonth' => '11',
            'expYear' => '2028',
            'cvc' => '123'
        ];

        $this->expectException(\RuntimeException::class);
        $this->shift4Provider->processPayment($params);
    }

    public function testValidateParamsMissingField()
    {
        $params = [
            'amount' => '490',
            'currency' => 'USD'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->shift4Provider->processPayment($params);
    }
}
