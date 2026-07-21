<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The master database seeder.
 *
 * Runs all application seeders in dependency order:
 *   1. SubscriptionPlanSeeder — must run first so the free plan FK is available
 *   2. User — test user (local dev only)
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

        // 2. Seed a default test user for local development
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
