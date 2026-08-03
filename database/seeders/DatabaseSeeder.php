<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The master database seeder.
 *
 * Runs all application seeders in dependency order:
 *   1. SubscriptionPlanSeeder — must run first so plan FKs are available
 *   2. AdminAccountSeeder — full-access owner account (always, all envs)
 *   3. DemoAccountSeeder / test user — local development only
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Subscription plans must exist before users reference them
        $this->call(SubscriptionPlanSeeder::class);

        // 2. Always seed the full-access admin account
        $this->call(AdminAccountSeeder::class);

        // 3. Seed sample/demo data only in non-production environments
        if (! app()->environment('production')) {
            $plan = \App\Models\SubscriptionPlan::where('name', 'enterprise')->first();

            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'subscription_plan_id' => $plan?->id,
            ]);

            $this->call(DemoAccountSeeder::class);
        }
    }
}
