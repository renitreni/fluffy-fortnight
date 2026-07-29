<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AggregateClicksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:aggregate-clicks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates raw clicks into hourly summaries for fast analytics querying';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lastId = \Illuminate\Support\Facades\Cache::get('analytics_last_click_id', 0);
        $chunkSize = 5000;

        $clicks = \App\Models\Click::where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->limit($chunkSize)
            ->get();

        if ($clicks->isEmpty()) {
            $this->info('No new clicks to aggregate.');
            return;
        }

        $aggregations = [];
        $maxId = $lastId;

        foreach ($clicks as $click) {
            // Group by start of the hour
            $hour = $click->clicked_at->format('Y-m-d H:00:00');
            
            $key = implode('|', [
                $click->link_id,
                $hour,
                $click->country,
                $click->device_type,
                $click->os,
                $click->browser,
                $click->referer_domain,
            ]);

            if (!isset($aggregations[$key])) {
                $aggregations[$key] = [
                    'link_id' => $click->link_id,
                    'hour' => $hour,
                    'country' => $click->country,
                    'device_type' => $click->device_type,
                    'os' => $click->os,
                    'browser' => $click->browser,
                    'referer_domain' => $click->referer_domain,
                    'clicks' => 0,
                ];
            }

            $aggregations[$key]['clicks']++;
            $maxId = $click->id;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($aggregations) {
            foreach ($aggregations as $row) {
                // Upsert or Increment
                $summary = \App\Models\ClickHourlySummary::firstOrCreate([
                    'link_id' => $row['link_id'],
                    'hour' => $row['hour'],
                    'country' => $row['country'],
                    'device_type' => $row['device_type'],
                    'os' => $row['os'],
                    'browser' => $row['browser'],
                    'referer_domain' => $row['referer_domain'],
                ]);

                $summary->increment('clicks', $row['clicks']);
            }
        });

        \Illuminate\Support\Facades\Cache::forever('analytics_last_click_id', $maxId);

        $this->info("Aggregated {$clicks->count()} clicks up to ID {$maxId}.");
    }
}
