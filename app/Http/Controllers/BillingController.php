<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    /**
     * Display the billing page with subscription plans.
     */
    public function index(Request $request): Response
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return Inertia::render('Billing/Index', [
            'plans' => $plans,
            'intent' => $request->user()->createSetupIntent(),
            'subscription' => clone $request->user()->subscription('default'),
        ]);
    }

    /**
     * Redirect to Stripe Checkout for a specific plan.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        $priceId = $request->interval === 'monthly' ? $plan->stripe_monthly_price_id : $plan->stripe_yearly_price_id;

        if (!$priceId) {
            return back()->with('error', 'This plan is currently unavailable for checkout.');
        }

        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.index', ['checkout' => 'success']),
                'cancel_url' => route('billing.index', ['checkout' => 'cancelled']),
            ]);
    }

    /**
     * Redirect to Stripe Billing Portal to manage subscription.
     */
    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing.index'));
    }
}
