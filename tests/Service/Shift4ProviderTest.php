<?php

namespace App\Tests\Service;

use App\Service\Shift4Provider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Shift4ProviderTest extends TestCase
{
    private $httpClient;
    private Shift4Provider $shift4Provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->shift4Provider = new Shift4Provider($this->httpClient);
    }

    public function testProcessPayment(): void
    {
        $params = ['amount' => 100, 'currency' => 'USD'];
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
                Shift4Provider::CHARGES_URL,
                $this->callback(function ($options) use ($params) {
                    return $options['json'] === $params
                        && $options['auth_basic'] === [Shift4Provider::API_KEY, ''];
                })
            )
            ->willReturn($mockResponse);

        $result = $this->shift4Provider->processPayment($params);

        $this->assertEquals(['status' => 'success'], $result);
    }
}
