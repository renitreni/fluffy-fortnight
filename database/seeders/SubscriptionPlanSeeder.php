<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Seeds the subscription_plans table with the three core tiers.
 *
 * This seeder uses `updateOrCreate` so it is safe to re-run without
 * creating duplicates. Stripe price IDs should be set via environment
 * variables or updated directly after Stripe products are created (Day 28).
 */
class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'free',
                'display_name' => 'Free',
                'features' => [
                    'analytics' => false,
                    'custom_domains' => false,
                    'api_access' => false,
                    'bulk_shortening' => false,
                    'password_links' => false,
                    'link_expiry' => false,
                ],
                'price_monthly' => null,
                'price_yearly' => null,
                'max_links' => 10,
                'max_workspaces' => 1,
                'max_custom_domains' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'pro',
                'display_name' => 'Pro',
                'features' => [
                    'analytics' => true,
                    'custom_domains' => true,
                    'api_access' => true,
                    'bulk_shortening' => true,
                    'password_links' => true,
                    'link_expiry' => true,
                    'qr_codes' => true,
                    'utm_builder' => true,
                ],
                'price_monthly' => 2.99,
                'price_yearly' => 24.99,
                'max_links' => 0, // unlimited
                'max_workspaces' => 3,
                'max_custom_domains' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Enterprise',
                'features' => [
                    'analytics' => true,
                    'custom_domains' => true,
                    'api_access' => true,
                    'bulk_shortening' => true,
                    'password_links' => true,
                    'link_expiry' => true,
                    'qr_codes' => true,
                    'utm_builder' => true,
                    'sso' => true,
                    'audit_logs' => true,
                    'rbac' => true,
                    'webhooks' => true,
                    'link_in_bio' => true,
                ],
                'price_monthly' => 6.99,
                'price_yearly' => 59.99,
                'max_links' => 0, // unlimited
                'max_workspaces' => 0, // unlimited
                'max_custom_domains' => 0, // unlimited
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }

        $this->command->info('Seeded 3 subscription plans: free, pro, enterprise.');
    }
}
