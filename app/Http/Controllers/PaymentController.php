<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{


    public function processPayment(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $subscription = Subscription::findOrFail($request->id);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $subscription?->price,
                'currency' => 'usd',
                'payment_method' => $request->payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);

            return response()->json(['success' => true, 'paymentIntent' => $paymentIntent]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
