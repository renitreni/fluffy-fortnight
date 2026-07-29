<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating Webhook test instances.
 *
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Webhook>
     */
    protected $model = Webhook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => null,
            'url' => 'https://'.$this->faker->domainName().'/webhooks/receive',
            'events' => $this->faker->randomElements(
                ['link.created', 'link.clicked', 'link.deleted', 'link.updated'],
                $this->faker->numberBetween(1, 3)
            ),
            'secret' => Str::random(32),
            'is_active' => true,
            'last_triggered_at' => null,
            'failure_count' => 0,
        ];
    }
}
