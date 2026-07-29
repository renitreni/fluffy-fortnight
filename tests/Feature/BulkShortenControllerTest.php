<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkShortening;
use App\Models\BulkJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkShortenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_shorten_page_is_displayed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('bulk.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Links/BulkShorten'));
    }

    public function test_csv_upload_dispatches_job_and_redirects(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $csvContent = "url\nhttps://example.com/1\nhttps://example.com/2";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('bulk.store'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('bulk.index'));
        $response->assertSessionHas('flash');

        $this->assertDatabaseHas('bulk_jobs', [
            'user_id' => $user->id,
            'original_filename' => 'test.csv',
            'total_rows' => 2,
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessBulkShortening::class, function ($job) use ($user) {
            return $job->bulkJob->user_id === $user->id;
        });
    }

    public function test_empty_csv_returns_error(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $file = UploadedFile::fake()->createWithContent('test.csv', '');

        $response = $this->actingAs($user)->post(route('bulk.store'), [
            'csv_file' => $file,
        ]);

        $response->assertSessionHasErrors(['csv_file']);
        Queue::assertNothingPushed();
    }

    public function test_user_can_download_completed_results(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['email_verified_at' => now()]);

        $bulkJob = BulkJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'result_file_path' => 'bulk_results/test.csv',
        ]);

        Storage::disk('local')->put('bulk_results/test.csv', 'url,short_url');

        $response = $this->actingAs($user)->get(route('bulk.download', $bulkJob));

        $response->assertOk();
        $response->assertDownload('bulk_results_'.$bulkJob->id.'.csv');
    }

    public function test_user_cannot_download_incomplete_results(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $bulkJob = BulkJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('bulk.download', $bulkJob));

        $response->assertNotFound();
    }
}
