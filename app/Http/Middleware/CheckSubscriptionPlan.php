<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $plan = $user->subscriptionPlan;

        if (!$plan) {
            // Default to free tier features if no plan is assigned
            // You might want to retrieve the default Free plan here
            abort(403, 'Subscription plan required.');
        }

        if ($feature) {
            // Check if feature is enabled in JSON features array
            $features = $plan->features ?? [];
            if (!isset($features[$feature]) || !$features[$feature]) {
                abort(403, "Your current plan does not support the '{$feature}' feature. Please upgrade.");
            }
        }

        return $next($request);
    }
}
