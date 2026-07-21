<?php

namespace Database\Factories;

use App\Models\BlockedUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating BlockedUrl test instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlockedUrl>
 */
class BlockedUrlFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\BlockedUrl>
     */
    protected $model = BlockedUrl::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = $this->faker->unique()->url();

        return [
            'url_hash'   => hash('sha256', strtolower(trim($url))),
            'url'        => $url,
            'reason'     => $this->faker->randomElement(['malicious', 'phishing', 'spam', 'manual']),
            'source'     => $this->faker->randomElement(['google_safe_browsing', 'phishtank', 'admin', null]),
            'blocked_by' => null,
        ];
    }
}
