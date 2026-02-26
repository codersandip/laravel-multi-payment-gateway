<?php

namespace VendorName\MultiPayment\Http\Controllers;

use Illuminate\Http\Request;
use VendorName\MultiPayment\Facades\MultiPayment;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;

class WebhookController
{
    /**
     * Handle incoming webhooks dynamically for any driver.
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, string $gateway)
    {
        // Try standardized signature headers based on gateway implementation
        $signature = $request->header('x-razorpay-signature') 
            ?? $request->header('stripe-signature') 
            ?? $request->header('x-webhook-signature')
            ?? '';

        try {
            // Attempt to verify and process webhook payload
            $response = MultiPayment::driver($gateway)->handleWebhook($request->all(), $signature);

            return response()->json([
                'status' => 'success',
                'handled_by' => $gateway,
                'data' => $response
            ], 200);

        } catch (PaymentGatewayException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
