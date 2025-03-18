<?php

namespace App\Services;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use Exception;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
class PayPalService
{

    public function createPayment(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->setAccessToken($provider->getAccessToken());

        $order = $provider->createOrder([
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->input('amount'),
                    ]
                ]
            ]
        ]);

        if (isset($order['id'])) {
            return response()->json(['approval_url' => $order['links'][1]['href']]);
        }

        return response()->json(['error' => 'Unable to create payment']);
    }
    public function capturePayment(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->setAccessToken($provider->getAccessToken());

        $response = $provider->capturePaymentOrder($request->input('order_id'));

        if ($response['status'] === 'COMPLETED') {
            return response()->json(['success' => 'Payment captured successfully']);
        }

        return response()->json(['error' => 'Payment capture failed']);
    }
}
