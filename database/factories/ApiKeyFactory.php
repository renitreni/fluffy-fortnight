<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating ApiKey test instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\ApiKey>
     */
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawKey = Str::random(40);

        return [
            'user_id'      => User::factory(),
            'workspace_id' => null,
            'name'         => $this->faker->words(3, true) . ' key',
            'key_hash'     => hash('sha256', $rawKey),
            'key_prefix'   => substr($rawKey, 0, 8),
            'abilities'    => ['links:read', 'links:create'],
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-7 days', 'now'),
            'expires_at'   => null,
            'is_active'    => true,
        ];
    }

    /**
     * State for a revoked (inactive) API key.
     */
    public function revoked(): static
    {
        return $this->state(['is_active' => false]);
    }
}
