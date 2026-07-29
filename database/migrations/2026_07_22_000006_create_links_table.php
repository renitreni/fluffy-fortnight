<?php

use App\Models\CustomDomain;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the links table.
 *
 * The core entity of the application. Stores every shortened URL along with
 * its settings (expiry, password, UTM params, deep links) and a denormalized
 * click_count for fast dashboard display without hitting the clicks table.
 *
 * Indexing strategy:
 *   - UNIQUE on short_code — primary redirect lookup
 *   - INDEX on (user_id) — user's link list
 *   - INDEX on (workspace_id) — workspace link list
 *   - INDEX on (custom_domain_id) — domain-scoped redirect lookup
 *   - INDEX on (is_active, expires_at) — composite for redirect eligibility check
 *   - INDEX on (created_at) — default dashboard sort
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The user who created this link');
            $table->foreignIdFor(Workspace::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Workspace this link belongs to; null if personal');
            $table->foreignIdFor(CustomDomain::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Custom domain to use for this short link; null = app default domain');

            // Core link fields
            $table->string('short_code', 20)->unique()->comment('Unique slug used in the short URL (Base62 encoded)');
            $table->text('original_url')->comment('The destination long URL');
            $table->string('title', 255)->nullable()->comment('Page title scraped from the destination URL <title>');
            $table->text('description')->nullable()->comment('Meta description scraped from the destination URL');

            // Link behaviour
            $table->boolean('is_custom_alias')->default(false)->comment('True when the short_code was chosen by the user');
            $table->string('password')->nullable()->comment('Bcrypt-hashed password gate; null = no password required');
            $table->timestamp('expires_at')->nullable()->comment('When the link expires; null = never expires');

            // Mobile deep linking (Day 15)
            $table->string('ios_deep_link')->nullable()->comment('iOS URI scheme or Universal Link for mobile redirect');
            $table->string('android_deep_link')->nullable()->comment('Android URI scheme or App Link for mobile redirect');

            // UTM parameter builder (Day 13)
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();

            // Counters & metadata
            $table->unsignedBigInteger('click_count')->default(0)->comment('Denormalized total click count for fast reads');
            $table->boolean('is_active')->default(true)->comment('Soft-deactivate without deleting; blocks redirect');
            $table->json('tags')->nullable()->comment('Array of tag strings for organization');

            $table->timestamps();
            $table->softDeletes();

            // Composite index for the redirect hot path: is the link live?
            $table->index(['is_active', 'expires_at'], 'links_active_expiry_index');
            // Sorting / filtering
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
