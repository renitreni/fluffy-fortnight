<?php

namespace App\Jobs;

use App\Exceptions\InvalidUrlException;
use App\Models\BulkJob;
use App\Models\Link;
use App\Notifications\BulkShorteningCompleted;
use App\Services\ShortCodeGeneratorService;
use App\Services\UrlNormalizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessBulkShortening implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public BulkJob $bulkJob,
        public string $filePath
    ) {}

    public function handle(UrlNormalizerService $normalizer, ShortCodeGeneratorService $generator): void
    {
        $this->bulkJob->update(['status' => 'processing']);

        $absolutePath = Storage::disk('local')->path($this->filePath);
        if (! file_exists($absolutePath)) {
            $this->bulkJob->update(['status' => 'failed']);

            return;
        }

        $resultFileName = 'bulk_results_'.$this->bulkJob->id.'_'.time().'.csv';
        $resultPath = 'bulk_results/'.$resultFileName;
        $absoluteResultPath = Storage::disk('local')->path($resultPath);

        // Ensure directory exists
        Storage::disk('local')->makeDirectory('bulk_results');

        $inputHandle = fopen($absolutePath, 'r');
        $outputHandle = fopen($absoluteResultPath, 'w');

        // Read header
        $header = fgetcsv($inputHandle);
        if (! $header) {
            $this->bulkJob->update(['status' => 'failed']);

            return;
        }

        // Output header
        $outputHeader = array_merge($header, ['short_url', 'status', 'error_message']);
        fputcsv($outputHandle, $outputHeader);

        // Find index of 'url' column (case insensitive)
        $urlIndex = false;
        $titleIndex = false;
        $customAliasIndex = false;

        foreach ($header as $index => $colName) {
            $colLower = strtolower(trim($colName));
            if ($colLower === 'url' || $colLower === 'original_url') {
                $urlIndex = $index;
            }
            if ($colLower === 'title') {
                $titleIndex = $index;
            }
            if ($colLower === 'custom_alias' || $colLower === 'alias') {
                $customAliasIndex = $index;
            }
        }

        if ($urlIndex === false) {
            fclose($inputHandle);
            fclose($outputHandle);
            $this->bulkJob->update(['status' => 'failed']);

            return;
        }

        $processed = 0;

        while (($row = fgetcsv($inputHandle)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            } // Skip empty rows

            $url = $row[$urlIndex] ?? null;
            $title = $titleIndex !== false ? ($row[$titleIndex] ?? null) : null;
            $customAlias = $customAliasIndex !== false ? ($row[$customAliasIndex] ?? null) : null;
            if ($customAlias === '') {
                $customAlias = null;
            }

            $status = 'success';
            $errorMessage = '';
            $shortUrl = '';

            if (! $url) {
                $status = 'error';
                $errorMessage = 'URL is required';
            } else {
                try {
                    $normalizedUrl = $normalizer->normalize($url);

                    // Deduplication check
                    $existingLink = null;
                    if (! $customAlias) {
                        $existingLink = Link::forUser($this->bulkJob->user_id)
                            ->where('original_url', $normalizedUrl)
                            ->first();
                    }

                    if ($existingLink) {
                        $shortUrl = rtrim(config('app.url'), '/').'/'.$existingLink->short_code;
                        $status = 'success'; // Treat as success, but it's reused
                    } else {
                        // Custom alias uniqueness check
                        if ($customAlias && Link::where('short_code', $customAlias)->exists()) {
                            throw new \Exception('Custom alias is already taken.');
                        }

                        $link = DB::transaction(function () use ($normalizedUrl, $title, $customAlias, $generator) {
                            $newLink = Link::create([
                                'user_id' => $this->bulkJob->user_id,
                                'original_url' => $normalizedUrl,
                                'title' => $title,
                                'is_active' => true,
                                'click_count' => 0,
                                'short_code' => $customAlias ?? 'tmp_'.substr(Str::uuid()->toString(), 0, 15),
                                'is_custom_alias' => $customAlias !== null,
                            ]);

                            if (! $customAlias) {
                                $generator->generateForLink($newLink);
                            }

                            return $newLink;
                        });

                        $shortUrl = rtrim(config('app.url'), '/').'/'.$link->short_code;
                    }

                } catch (InvalidUrlException $e) {
                    $status = 'error';
                    $errorMessage = $e->getMessage();
                } catch (\Exception $e) {
                    $status = 'error';
                    $errorMessage = $e->getMessage();
                }
            }

            $outputRow = array_merge($row, [$shortUrl, $status, $errorMessage]);
            fputcsv($outputHandle, $outputRow);

            $processed++;

            // Update status every 50 rows
            if ($processed % 50 === 0) {
                $this->bulkJob->update(['processed_rows' => $processed]);
            }
        }

        fclose($inputHandle);
        fclose($outputHandle);

        $this->bulkJob->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'result_file_path' => $resultPath,
        ]);

        // Send Notification
        $this->bulkJob->user->notify(new BulkShorteningCompleted($this->bulkJob));
    }
}
