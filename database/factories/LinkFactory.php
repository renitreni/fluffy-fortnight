<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating Link test instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Link>
     */
    protected $model = Link::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'workspace_id'     => null,
            'custom_domain_id' => null,
            'short_code'       => Str::random(6),
            'original_url'     => $this->faker->url(),
            'title'            => $this->faker->sentence(4),
            'description'      => $this->faker->optional()->sentence(),
            'is_custom_alias'  => false,
            'password'         => null,
            'expires_at'       => null,
            'ios_deep_link'    => null,
            'android_deep_link' => null,
            'utm_source'       => null,
            'utm_medium'       => null,
            'utm_campaign'     => null,
            'utm_term'         => null,
            'utm_content'      => null,
            'click_count'      => 0,
            'is_active'        => true,
            'tags'             => null,
        ];
    }

    /**
     * State for an expired link (past expiry date).
     */
    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * State for a password-protected link.
     */
    public function passwordProtected(): static
    {
        return $this->state([
            'password' => 'secret',
        ]);
    }

    /**
     * State for an inactive (soft-deactivated) link.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * State for a link with UTM parameters pre-filled.
     */
    public function withUtm(): static
    {
        return $this->state([
            'utm_source'   => $this->faker->randomElement(['google', 'twitter', 'email']),
            'utm_medium'   => $this->faker->randomElement(['cpc', 'social', 'newsletter']),
            'utm_campaign' => $this->faker->slug(2),
        ]);
    }
}
