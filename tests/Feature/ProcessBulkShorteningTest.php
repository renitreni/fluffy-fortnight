<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkShortening;
use App\Models\BulkJob;
use App\Models\User;
use App\Notifications\BulkShorteningCompleted;
use App\Services\ShortCodeGeneratorService;
use App\Services\UrlNormalizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessBulkShorteningTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_valid_csv_and_sends_notification(): void
    {
        Storage::fake('local');
        Notification::fake();

        $user = User::factory()->create();
        $bulkJob = BulkJob::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'original_filename' => 'test.csv',
        ]);

        $csvContent = "url,title,custom_alias\n";
        $csvContent .= "https://example.com/1,Test 1,\n";
        $csvContent .= "https://example.com/2,,custom-alias-1\n";
        $csvContent .= "ftp://example.com,,\n";

        Storage::disk('local')->put('bulk_imports/test.csv', $csvContent);

        $job = new ProcessBulkShortening($bulkJob, 'bulk_imports/test.csv');
        $job->handle(app(UrlNormalizerService::class), app(ShortCodeGeneratorService::class));

        $bulkJob->refresh();

        $this->assertEquals('completed', $bulkJob->status);
        $this->assertEquals(3, $bulkJob->processed_rows);
        $this->assertNotNull($bulkJob->result_file_path);

        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com/1',
            'title' => 'Test 1',
        ]);

        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com/2',
            'short_code' => 'custom-alias-1',
        ]);

        // Check result file
        $resultContent = Storage::disk('local')->get($bulkJob->result_file_path);
        $this->assertStringContainsString('https://example.com/1,"Test 1",,http://localhost:8080', $resultContent);
        $this->assertStringContainsString('https://example.com/2,,custom-alias-1,http://localhost:8080/custom-alias-1', $resultContent);
        $this->assertStringContainsString('ftp://example.com,,,', $resultContent);
        $this->assertStringContainsString('Only http and https URLs are accepted', $resultContent); // the error message

        Notification::assertSentTo(
            $user,
            BulkShorteningCompleted::class
        );
    }

    public function test_it_fails_if_file_missing(): void
    {
        Storage::fake('local');

        $bulkJob = BulkJob::factory()->create(['status' => 'pending']);

        $job = new ProcessBulkShortening($bulkJob, 'bulk_imports/missing.csv');
        $job->handle(app(UrlNormalizerService::class), app(ShortCodeGeneratorService::class));

        $bulkJob->refresh();
        $this->assertEquals('failed', $bulkJob->status);
    }
}
