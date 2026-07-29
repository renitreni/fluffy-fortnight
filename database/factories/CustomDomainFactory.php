<?php

namespace Database\Factories;

use App\Models\CustomDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating CustomDomain test instances.
 *
 * @extends Factory<CustomDomain>
 */
class CustomDomainFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomDomain>
     */
    protected $model = CustomDomain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => null,
            'user_id' => User::factory(),
            'domain' => 'link.'.$this->faker->unique()->domainName(),
            'is_verified' => false,
            'verified_at' => null,
            'verification_token' => Str::random(32),
            'ssl_status' => 'pending',
        ];
    }

    /**
     * State for a fully verified and active domain.
     */
    public function verified(): static
    {
        return $this->state([
            'is_verified' => true,
            'verified_at' => now(),
            'ssl_status' => 'active',
        ]);
    }
}
