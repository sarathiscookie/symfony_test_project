<?php

namespace App\Tests\Service;

use App\Service\AciProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AciPaymentServiceTest extends TestCase
{
    private $httpClient;
    private AciProvider $aciProvider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->aciProvider = new AciProvider($this->httpClient);
    }

    public function testProcessPayment(): void
    {
        $params = ['amount' => 150, 'currency' => 'GBP'];
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['status' => 'success']);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                AciProvider::PAYMENTS_URL,
                $this->callback(function ($options) use ($params) {
                    return $options['json'] === $params
                        && $options['auth_bearer'] === AciProvider::BEARER_TOKEN;
                })
            )
            ->willReturn($mockResponse);

        $result = $this->aciProvider->processPayment($params);

        $this->assertEquals(['status' => 'success'], $result);
    }
}
