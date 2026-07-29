<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for generating Workspace test instances.
 *
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Workspace>
     */
    protected $model = Workspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'logo' => null,
            'custom_domain_limit' => 1,
        ];
    }
}
