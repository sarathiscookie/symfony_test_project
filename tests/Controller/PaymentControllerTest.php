<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testHandlePaymentWithValidProvider(): void
    {
        // Create a client to simulate HTTP requests
        $client = static::createClient();

        // Send a POST request to the endpoint with a valid provider
        $client->request(
            'POST',
            '/api/payment/shift4',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['amount' => 100, 'currency' => 'USD'])
        );

        // Assert the response status is 200 (success)
        $this->assertResponseStatusCodeSame(200);

        // Assert the response contains the expected JSON structure
        $this->assertJsonEquals(
            [
                'message' => 'Payment processed successfully',
            ],
            $client->getResponse()->getContent()
        );
    }

    public function testHandlePaymentWithInvalidProvider(): void
    {
        // Create a client to simulate HTTP requests
        $client = static::createClient();

        // Send a POST request to the endpoint with an invalid provider
        $client->request(
            'POST',
            '/api/payment/invalid',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['amount' => 100, 'currency' => 'USD'])
        );

        // Assert the response status is 400 (bad request)
        $this->assertResponseStatusCodeSame(400);

        // Assert the response contains the expected error message
        $this->assertJsonEquals(
            [
                'error' => 'Invalid argument provided',
            ],
            $client->getResponse()->getContent()
        );
    }

    private function assertJsonEquals(array $expected, string $actualJson): void
    {
        $actual = json_decode($actualJson, true);
        $this->assertEquals($expected, $actual);
    }
}
