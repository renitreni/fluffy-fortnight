<?php

namespace Database\Factories;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Click test instances.
 *
 * @extends Factory<Click>
 */
class ClickFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Click>
     */
    protected $model = Click::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ip = $this->faker->ipv4();

        return [
            'link_id' => Link::factory(),
            'ip_hash' => hash('sha256', $ip),
            'country' => $this->faker->countryCode(),
            'region' => $this->faker->optional()->state(),
            'city' => $this->faker->optional()->city(),
            'latitude' => $this->faker->optional()->latitude(),
            'longitude' => $this->faker->optional()->longitude(),
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet', 'bot', 'unknown']),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android', null]),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge', null]),
            'referer' => $this->faker->optional()->url(),
            'referer_domain' => $this->faker->optional()->domainName(),
            'user_agent' => $this->faker->userAgent(),
            'clicked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
