<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::all();
        return response()->json(['data' => $subscriptions]);
    }

    public function buy($id)
    {
        $subscription = Subscription::findOrFail($id);

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $provider = new PayPalClient();
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $order = $provider->createOrder([
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD", // Adjust currency if needed
                        "value" => $subscription->price,
                    ],
                    "description" => $subscription->name,
                ]
            ],
            "application_context" => [
                "cancel_url" => route('subscriptions.cancel'), // Production cancel URL
                "return_url" => route('subscriptions.success'), // Production success URL
            ]
        ]);

        if (isset($order['links'][1]['href'])) {
            return response()->json(['redirect_url' => $order['links'][1]['href']], 200);
        }

        return response()->json(['error' => 'Failed to create PayPal order'], 500);
    }

    public function success(Request $request)
    {
        $provider = new PayPalClient();
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request->query('token'));

        if (isset($response['status']) && $response['status'] === 'COMPLETED') {
            return response()->json(['message' => 'Payment successful', 'data' => $response], 200);
        }

        return response()->json(['error' => 'Payment not completed'], 400);
    }

    public function cancel()
    {
        return redirect()->route('subscriptions.index')->with('error', 'Payment was cancelled.');
    }
}
