<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a full-access admin account with the Enterprise plan forever.
 *
 * This account has unrestricted access to all platform features
 * and is intended for the platform owner / administrator.
 */
class AdminAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = SubscriptionPlan::where('name', 'enterprise')->first();

        User::updateOrCreate(
            ['email' => 'admin@elido.local'],
            [
                'name' => 'Admin',
                'email' => 'admin@elido.local',
                'password' => bcrypt('admin123'),
                'subscription_plan_id' => $plan?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Seeded admin account: admin@elido.local / admin123');
    }
}
