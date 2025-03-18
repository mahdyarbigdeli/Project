<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Services\PayPalService;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    private $paypal;

    public function __construct(PayPalService $paypal)
    {
        $this->paypal = $paypal;
    }

    public function createPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'id' => 'required',
            ]);

            session(['subscription' => $request->id]);
            session(['username' =>  $request->username]);

            $subscription = Subscription::find($request->id);
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->setAccessToken($provider->getAccessToken());

            $order = $provider->createOrder([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => $subscription?->price,
                        ]
                    ]
                ]
            ]);

            if (isset($order['id'])) {
                return response()->json(['redirect_url' => $order['links'][1]['href']]);
            }

            return response()->json(['error' => 'Unable to create payment']);
        } catch (\Exception $e) {
            // Other errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function capturePayment(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->setAccessToken($provider->getAccessToken());
        $order = time();
        $response = $provider->capturePaymentOrder($order);

        if ($response['status'] === 'COMPLETED') {
            return response()->json(['success' => 'Payment captured successfully']);
        }

        return response()->json(['error' => 'Payment capture failed']);
    }
}
