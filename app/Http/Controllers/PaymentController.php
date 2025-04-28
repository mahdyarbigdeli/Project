<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PaymentController extends Controller
{
    private $client;

    public function __construct()
    {

        // Set up PayPal API client using the sandbox environment
        $environment = new ProductionEnvironment(env('PAYPAL_LIVE_CLIENT_ID'), env('PAYPAL_LIVE_CLIENT_SECRET'));
        $this->client = new PayPalHttpClient($environment);

    }

    /**
     * Create a PayPal Order for a Single Item
     */
    public function createOrder(Request $request)
    {
        // Validate input parameters
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
            'sku' => 'required|string|max:255',
            // 'quantity' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'payment_source' => 'nullable|string|in:paypal,applepay,card',
        ]);

        try {
            // Calculate the total amount
            $totalAmount = number_format($request->price, 2, '.', '');

            // Build the order payload
            $orderBody = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $request->currency,
                            'value' => $totalAmount,
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => $request->currency,
                                    'value' => $totalAmount,
                                ],
                            ],
                        ],
                        'items' => [
                            [
                                'name' => $request->name,
                                'sku' => $request->sku,
                                'unit_amount' => [
                                    'currency_code' => $request->currency,
                                    'value' => number_format($request->price, 2, '.', ''),
                                ],
                                'quantity' => 1,
                            ],
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' => route('payment.success'),
                    'cancel_url' => route('payment.cancel'),
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                ],
            ];

            if ($request->filled('payment_source')) {
                $paymentSource = $request->payment_source;
            } else {
                $paymentSource = 'paypal';
            }

            // $orderBody['payment_source'] = [
            //     $paymentSource => []
            // ];

            // Send the order creation request to PayPal
            $orderRequest = new OrdersCreateRequest();
            $orderRequest->prefer('return=representation');
            $orderRequest->body = $orderBody;

            $response = $this->client->execute($orderRequest);


            // Return the response with the order ID and approval URL
            return response()->json([
                'status' => 'success',
                'order_id' => $response->result->id,
                'approval_url' => collect($response->result->links)->where('rel', 'approve')->first()->href,
            ]);
        }
            catch (\Exception $e) {
                \Log::error('PayPal API Error: '.$e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }
    }

    /**
     * Capture a PayPal Order
     */
    public function captureOrder($orderId)
    {
        try {
            $captureRequest = new OrdersCaptureRequest($orderId);
            $response = $this->client->execute($captureRequest);

            // Return the capture details
            return response()->json([
                'status' => 'success',
                'data' => $response->result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
