<?php

namespace App\Controller;

use JsonException;
use RuntimeException;
use InvalidArgumentException;
use App\Service\PaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Processes a payment request for the specified provider.
     *
     * @param string $provider The payment provider (aci or shift4).
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse
     */
    #[Route('/app/payment/{provider}', name: 'app_payment', methods: ['POST'])]
    public function handlePayment(string $provider, Request $request): JsonResponse
    {
        try {
            // Parse the request body to an array
            $params = $request->toArray();

            // Check if required parameters are provided
            if (!isset($params['amount']) || !isset($params['currency'])) {
                return new JsonResponse(['error' => 'Amount and currency are required.'], 400);
            }

            // Process the payment through the PaymentService
            $response = $this->paymentService->processPayment($provider, $params);

            // Return the successful response with payment data
            return new JsonResponse([
                'message' => 'Payment processed successfully.',
                'data' => $response,
            ]);

        } catch (JsonException $e) {
            // Handle invalid JSON input
            return new JsonResponse(['error' => 'Invalid JSON in request.'], 400);
        } catch (InvalidArgumentException $e) {
            // Handle invalid provider or missing parameters
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            // Handle issues during payment processing
            return new JsonResponse(['error' => 'Error occurred while processing payment: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return new JsonResponse(['error' => 'An unexpected error occurred.'], 500);
        }
    }
}
