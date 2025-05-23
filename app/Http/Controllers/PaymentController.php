<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
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
        $client = "AWs7cWCUvAxkwcUkkI_k0mvfV2QyV-YT1M_Gu9odwlwxyk7pnthlwEU3ITh76l9b0vTNBVOYqf3NiW1a";
        $secret = "EDazYypXgR1VsBfP5QJQPC6MviaRjihKSQSTIfZYsooWXuOmOAultw2sJ4mgdLg6XIxar2bi_7hTxKOx";
        // $environment = new ProductionEnvironment(env('PAYPAL_LIVE_CLIENT_ID'), env('PAYPAL_LIVE_CLIENT_SECRET'));
        $environment = new ProductionEnvironment($client, $secret);
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
            'username' => 'required|string|max:255',
        ]);

        try {
            // Calculate the total amount
            $totalAmount = number_format($request->price, 2, '.', '');

            $subscription = Subscription::where('price', $request->price)->first();
            session(['subscription' => $subscription->id]);
            session(['username' => $request->username]);

            Session::put('username', $request->username);
            Session::put('subscription', $subscription->id);
            Session::save();



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
                    // 'return_url' => 'https://bo.tamasha.me/api/paypal/payment-success', //route('payment.success'),
                    // 'cancel_url' => 'https://bo.tamasha.me/api/paypal/payment-cancel', // route('payment.cancel'),
                    'return_url' => 'https://bo.tamasha.me/api/paypal/payment-success', // route('payment.success'),
                    'cancel_url' => 'https://bo.tamasha.me/api/paypal/payment-cancel', // route('payment.cancel'),

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

            // $username = session('username');
            // $subscription_id = session('subscription');


            $response = $this->client->execute($orderRequest);

            UserSubscription::create([
                'user_name' => $request->username,
                'subscription_id' => $subscription->id,
                'order_id' => $response->result->id
            ]);

            // Return the response with the order ID and approval URL
            return response()->json([
                'status' => 'success',
                'order_id' => $response->result->id,
                'approval_url' => collect($response->result->links)->where('rel', 'approve')->first()->href,
            ]);
        } catch (\Exception $e) {
            Log::info('PayPal API Error: ' . $e->getMessage());
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


            // // Return the capture details
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


    public function paymentSuccess(Request $request)
    {
        // $subscriptionId = session('subscription');
        // $username = session('username');
        // $captureRequest = new OrdersCaptureRequest($orderId);
        // $response = $this->client->execute($captureRequest);


        $username = Session::get('username');
        $subscriptionId = Session::get('subscription');

        $userSubscription = UserSubscription::where('order_id', $request['token'])->first();
        $subscription = Subscription::find($userSubscription->subscription_id);
        $response = Http::get('http://tamasha-tv.com:25461/usernopass.php', [
            'username' =>  $userSubscription->user_name
        ]);


        $username = $userSubscription->user_name;
        $password = "";
        if ($response->successful()) {

            $raw = $response->body();
            $firstDecode = json_decode($response->body(), true);

            if (is_string($firstDecode)) {
                $finalData = json_decode($firstDecode, true);
                $password = $finalData['password'];
            }
        }
        switch ($subscription->price) {
            case '599':
                $period = "lifetime";
                break;

            case '120':
                $period = "1year";

                break;
            case '90':
                $period = "6month";

                break;
            case '20':
                $period = "1month";
            case '1':
                $period = "1month";

                break;
        }

        $response = $this->updateUser($username, $password, $period);
        // Log::info('PayPal API Data: ' . $subscription->price);
        $data = $response->getData(true);
        if (isset($data['error'])) {
            return response()->json(['message' => 'Update failed', 'details' => $data['error']], 400);
        }

        // Log::info('PayPal API Data: ' . $data);
        $mailData = [
            'title' => ' پرداخت موفق',
            'subject' => ' تایید پرداخت ',
            'body' => 'خرید اشتراک شما با موفقیت انجام شد . با معرفی هر یک از مشترکان جدید به ما،‌یک ماه اشتراک اضافه رایگان دریافت نمایید'
        ];
        Mail::send('emails.userMail', ['mailData' => $mailData], function ($mail) use ($username) {
            $mail->from(env('MAIL_USERNAME'), env('MAIL_FROM_NAME'))
                ->to($username)
                ->subject('پرداخت موفق');
        });

        // Log::info('PayPal API Data: Email sent');
        // $userSubscription = UserSubscription::where('username', $username)
        //     ->where('subscription_id', $subscriptionId)
        //     ->first();

        // $orderId = $request->query('token');
        // $result = $this->captureOrder($orderId);
        // if ($result['status'] === 'success') {

        return redirect()->away('https://user.tamasha.me/subscriptions/success');
        // }
    }

    public function paymentCancel(Request $request)
    {
        return redirect()->away('https://user.tamasha.me/subscriptions/cancel');
    }


    public function updateUser($username, $password, $period)
    {
        $panelUrl = 'http://tamasha-tv.com:25461/edituser.php';
        // Configuration settings
        try {

            $postResponse = Http::asForm()->post($panelUrl . "?username=" . $username . "&password=" . $password . "&period=" . $period);
            if ($postResponse->failed()) {
                return response()->json(['error' => 'API request failed.'], 500);
            }

            $apiResult = $postResponse->json();

            if (isset($apiResult['error'])) {
                return response()->json(['error' => 'API Error: ' . $apiResult['error']], 400);
            }
            return response()->json(['message' => 'User updated successfully.']);
        } catch (\Exception $e) {
            // Handle and log the exception
            // \Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
