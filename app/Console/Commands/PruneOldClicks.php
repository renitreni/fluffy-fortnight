<?php

namespace App\Console\Commands;

use App\Models\Click;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneOldClicks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clicks:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old clicks based on user data retention settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting click pruning process...');
        $totalDeleted = 0;

        // Fetch users who have a specific data retention policy (not null)
        $users = User::whereNotNull('data_retention_days')->get();

        foreach ($users as $user) {
            $retentionDate = Carbon::now()->subDays($user->data_retention_days);
            
            // Delete clicks for links owned by this user, older than retentionDate
            $deleted = DB::table('clicks')
                ->join('links', 'clicks.link_id', '=', 'links.id')
                ->where('links.user_id', $user->id)
                ->where('clicks.clicked_at', '<', $retentionDate)
                ->delete();

            if ($deleted > 0) {
                $totalDeleted += $deleted;
                $this->info("Deleted {$deleted} clicks for user {$user->id} (older than {$user->data_retention_days} days).");
                Log::info("Pruned {$deleted} clicks for user {$user->id}.");
            }
        }

        $this->info("Completed. Total clicks deleted: {$totalDeleted}");
    }
}
