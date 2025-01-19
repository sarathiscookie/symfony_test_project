<?php

namespace App\Tests\Service;

use App\Service\PaymentService;
use App\Service\Shift4Provider;
use App\Service\AciProvider;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    private PaymentService $paymentService;
    private $shift4Provider;
    private $aciProvider;

    protected function setUp(): void
    {
        $this->shift4Provider = $this->createMock(Shift4Provider::class);
        $this->aciProvider = $this->createMock(AciProvider::class);
        $this->paymentService = new PaymentService($this->shift4Provider, $this->aciProvider);
    }

    public function testProcessPaymentWithShift4(): void
    {
        $params = ['amount' => 100, 'currency' => 'USD'];

        $this->shift4Provider
            ->expects($this->once())
            ->method('processPayment')
            ->with($params)
            ->willReturn(['status' => 'success', 'provider' => 'shift4']);

        $result = $this->paymentService->processPayment('shift4', $params);

        $this->assertEquals(['status' => 'success', 'provider' => 'shift4'], $result);
    }

    public function testProcessPaymentWithAci(): void
    {
        $params = ['amount' => 200, 'currency' => 'EUR'];

        $this->aciProvider
            ->expects($this->once())
            ->method('processPayment')
            ->with($params)
            ->willReturn(['status' => 'success', 'provider' => 'aci']);

        $result = $this->paymentService->processPayment('aci', $params);

        $this->assertEquals(['status' => 'success', 'provider' => 'aci'], $result);
    }

    public function testProcessPaymentWithInvalidProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid payment provider.');

        $this->paymentService->processPayment('unknown', ['amount' => 300, 'currency' => 'GBP']);
    }
}
