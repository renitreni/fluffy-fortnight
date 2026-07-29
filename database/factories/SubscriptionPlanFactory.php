<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating SubscriptionPlan test instances.
 *
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SubscriptionPlan>
     */
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(1),
            'display_name' => $this->faker->words(2, true),
            'features' => ['analytics' => true, 'custom_domains' => false],
            'price_monthly' => $this->faker->randomFloat(2, 0, 99),
            'price_yearly' => $this->faker->randomFloat(2, 0, 990),
            'stripe_monthly_price_id' => null,
            'stripe_yearly_price_id' => null,
            'max_links' => $this->faker->randomElement([10, 100, 0]),
            'max_workspaces' => $this->faker->randomElement([1, 3, 10]),
            'max_custom_domains' => $this->faker->randomElement([0, 1, 5]),
            'is_active' => true,
        ];
    }

    /**
     * State for the free plan.
     */
    public function free(): static
    {
        return $this->state([
            'name' => 'free',
            'display_name' => 'Free',
            'price_monthly' => null,
            'price_yearly' => null,
            'max_links' => 10,
            'max_workspaces' => 1,
            'max_custom_domains' => 0,
            'features' => ['analytics' => false, 'custom_domains' => false, 'api_access' => false],
        ]);
    }

    /**
     * State for the pro plan.
     */
    public function pro(): static
    {
        return $this->state([
            'name' => 'pro',
            'display_name' => 'Pro',
            'price_monthly' => 12.00,
            'price_yearly' => 120.00,
            'max_links' => 0,
            'max_workspaces' => 3,
            'max_custom_domains' => 3,
            'features' => ['analytics' => true, 'custom_domains' => true, 'api_access' => true, 'bulk_shortening' => true],
        ]);
    }

    /**
     * State for the enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state([
            'name' => 'enterprise',
            'display_name' => 'Enterprise',
            'price_monthly' => 49.00,
            'price_yearly' => 490.00,
            'max_links' => 0,
            'max_workspaces' => 0,
            'max_custom_domains' => 0,
            'features' => ['analytics' => true, 'custom_domains' => true, 'api_access' => true, 'bulk_shortening' => true, 'sso' => true, 'audit_logs' => true, 'rbac' => true],
        ]);
    }
}
