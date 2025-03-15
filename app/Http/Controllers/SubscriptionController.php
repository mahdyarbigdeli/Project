<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::all();
        return response()->json(['data' => $subscriptions]);
    }

    public function buy(Request $request, $id)
    {
        try {

            $subscription = Subscription::findOrFail($id);

            // $validated = $request->validate([
            //     'username' => 'required|string',
            // ]);


            // session(['subscription' => $id]);
            // session(['username' => $request->username]);

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
        } catch (\Exception $e) {
            // Other errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        $provider = new PayPalClient();
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request->query('token'));

        //update period
        $panelUrl = 'http://tamasha-tv.com:25461/usernopass.php';
        $expireDate = strtotime('1day');
        if ($expireDate === false) {
            return response()->json(['error' => 'Invalid period format.'], 400);
        }
        $response = Http::asForm()->post($panelUrl . "?username=" . $request->username);
        if ($response->failed()) {
            return response()->json(['error' => 'API request failed. Could not connect to the API.'], 500);
        }
        $apiResult = $response->json();
        $apiResult = json_decode($apiResult);
        if (isset($apiResult->error)) {
            return response()->json(['error' => 'API Error: ' . $apiResult->error], 400);
        }

        $panelUrl = 'http://tamasha-tv.com:25461/edituser.php';
        $maxConnections = 1;
        $period = '1year'; //todo change it

        $expireDate = strtotime($period);
        if ($expireDate === false) {
            return response()->json(['error' => 'Invalid period format.'], 400);
        }
        $postData = array(
            'username' => $request->username,
            'password' => $apiResult->password,
            'user_data' => array(
                'max_connections' => $maxConnections,
                'is_restreamer' => 0,
                'exp_date' => $expireDate,
            ),
        );
        $response = Http::asForm()->post($panelUrl . "?username=" . $request->username . "&password=" . $apiResult->password . "&period=" . $period, $postData);

        // Check if the request failed
        if ($response->failed()) {
            return response()->json(['error' => 'API request failed. Could not connect to the API.'], 500);
        }
        // Decode the JSON response
        $apiResult = $response->json();
        if (isset($response['status']) && $response['status'] === 'success') {
            return response()->json(['message' => 'Payment successful', 'data' => $apiResult], 200);
        }

        return response()->json(['error' => 'Payment not completed'], 400);
    }

    public function cancel(Request $request)
    {

        return redirect('/subscriptions/cancel?token=' . $request?->token); //->with('token', $request?->token);

        // return redirect()->route('subscriptions.index')->with('error', 'Payment was cancelled.');
    }
}
