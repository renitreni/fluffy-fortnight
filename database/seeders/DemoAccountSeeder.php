<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\BioPage;
use App\Models\BlockedUrl;
use App\Models\BulkJob;
use App\Models\Click;
use App\Models\ClickHourlySummary;
use App\Models\CustomDomain;
use App\Models\Link;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Webhook;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds one fully-populated "demo" account that touches every model in the
 * app, so local development and manual QA always have realistic data to
 * work with: seeder@example.com / password.
 */
class DemoAccountSeeder extends Seeder
{
    /**
     * Seed the demo account and its related records.
     */
    public function run(): void
    {
        $plan = SubscriptionPlan::where('name', 'enterprise')->first();

        $owner = User::factory()->create([
            'name' => 'Seeder Demo',
            'email' => 'seeder@example.com',
            'subscription_plan_id' => $plan?->id,
        ]);

        $member = User::factory()->create([
            'name' => 'Demo Member',
            'email' => 'seeder.member@example.com',
            'subscription_plan_id' => $plan?->id,
        ]);

        $workspace = Workspace::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Demo Workspace',
        ]);

        $owner->update(['current_workspace_id' => $workspace->id]);

        $workspace->members()->attach([
            $owner->id => ['role' => 'admin', 'joined_at' => now()],
            $member->id => ['role' => 'editor', 'joined_at' => now()],
        ]);

        WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'role' => 'viewer',
            'token' => Str::random(32),
        ]);

        $domain = CustomDomain::factory()->verified()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'domain' => 'demo.example.com',
        ]);

        $links = Link::factory()->count(5)->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'custom_domain_id' => $domain->id,
        ]);

        $links->each(function (Link $link): void {
            Click::factory()->count(10)->create(['link_id' => $link->id]);
            ClickHourlySummary::factory()->create(['link_id' => $link->id]);
        });

        $bioPage = BioPage::create([
            'user_id' => $owner->id,
            'alias' => 'seeder-demo',
            'title' => 'Seeder Demo Links',
            'description' => 'A demo bio page created by the seeder.',
        ]);

        $links->each(function (Link $link, int $index) use ($bioPage): void {
            $bioPage->links()->attach($link->id, ['display_order' => $index]);
        });

        ApiKey::factory()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
        ]);

        Webhook::factory()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
        ]);

        BulkJob::factory()->create([
            'user_id' => $owner->id,
        ]);

        AuditLog::create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'action' => 'workspace.created',
            'description' => 'Demo workspace created by seeder.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder/1.0',
        ]);

        AuditLog::create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'action' => 'workspace.member_added',
            'description' => 'Demo member added to workspace.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder/1.0',
        ]);

        BlockedUrl::factory()->create([
            'blocked_by' => $owner->id,
        ]);

        $this->command->info('Seeded demo account across all models: seeder@example.com / password');
    }
}
