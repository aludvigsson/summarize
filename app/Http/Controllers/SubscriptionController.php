<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function checkout(Request $request)
    {
        // Ensure the user is authenticated
        $user = $request->user();

        // Redirect to Stripe Checkout session
        return $user->newSubscription('default', 'price_basic_monthly')
            ->trialDays(5) // Add a 5-day trial period
            ->allowPromotionCodes() // Allow users to enter promotional codes
            ->checkout([
                'success_url' => route('subscription.success'), // Define where to redirect upon success
                'cancel_url' => route('subscription.cancel'),  // Define where to redirect if the user cancels
            ]);
    }

    public function success()
    {
        return response()->view('subscription.success'); // Return a view or message for success
    }

    public function cancel()
    {
        return response()->view('subscription.cancel'); // Return a view or message for cancellation
    }
}
