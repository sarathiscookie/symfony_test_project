<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PaymentService;

/**
 * Handles payment-related operations for various providers.
 */
class PaymentController extends AbstractController
{
    /**
     * Processes a payment request for the specified provider.
     *
     * @param string $provider The payment provider (e.g., "shift4", "aci").
     * @param Request $request The HTTP request object containing JSON payload.
     * @param PaymentService $paymentService The service to process the payment.
     *
     * @return JsonResponse JSON response with the result of the payment processing.
     */
    #[Route('/app/payment/{provider}', name: 'app_payment', methods: ['POST'])]
    public function handlePayment(string $provider, Request $request, PaymentService $paymentService): JsonResponse
    {
        // Parse and validate the JSON request body
        try {
            $params = $request->toArray(); // Decodes JSON payload
        } catch (\JsonException $e) {
            return $this->json(
                ['error' => 'Invalid JSON payload', 'details' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // Validate required parameters
        if (empty($params['amount']) || empty($params['currency'])) {
            return $this->json(
                ['error' => 'Missing required fields: amount and/or currency'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            // Process the payment via the specified provider
            $response = $paymentService->processPayment($provider, $params);

            return $this->json(
                ['message' => 'Payment processed successfully', 'data' => $response],
                JsonResponse::HTTP_OK
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ['error' => 'Invalid argument provided', 'details' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        } catch (\RuntimeException $e) {
            return $this->json(
                ['error' => 'Payment processing failed', 'details' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'An unexpected error occurred', 'details' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
