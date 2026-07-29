<?php

namespace Database\Factories;

use App\Models\BulkJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BulkJob>
 */
class BulkJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'original_filename' => 'links.csv',
            'result_file_path' => null,
            'total_rows' => 10,
            'processed_rows' => 0,
        ];
    }
}
