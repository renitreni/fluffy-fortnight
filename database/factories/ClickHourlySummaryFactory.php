<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClickHourlySummary>
 */
class ClickHourlySummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => \App\Models\Link::factory(),
            'hour' => now()->startOfHour(),
            'country' => $this->faker->countryCode,
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'iOS', 'Android']),
            'browser' => $this->faker->randomElement(['Chrome', 'Safari', 'Firefox']),
            'referer_domain' => $this->faker->domainName,
            'clicks' => $this->faker->numberBetween(1, 100),
        ];
    }
}
