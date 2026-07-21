<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the clicks table.
 *
 * An append-only, high-volume event log. Every redirect fires an async job
 * that inserts a row here. IP addresses are SHA-256 hashed (GDPR compliance)
 * before storage — the raw IP is never persisted.
 *
 * Indexing strategy:
 *   - INDEX on (link_id, clicked_at) — primary time-series analytics query
 *   - INDEX on (country)             — geo breakdown
 *   - INDEX on (device_type)         — device breakdown
 *   - INDEX on (referer_domain)      — referrer breakdown
 *   - INDEX on (clicked_at)          — global time-series / aggregation jobs
 *
 * Design notes:
 *   - No updated_at column — rows are never updated after insert.
 *   - No softDeletes — rows are hard-deleted per data retention policy.
 *   - No FK constraint on link_id intentionally omitted for insert throughput;
 *     referential integrity is enforced at the application layer.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id')->index()->comment('References links.id — no FK constraint for write throughput');

            // Privacy-compliant geo data (IP hashed at ingestion)
            $table->string('ip_hash', 64)->nullable()->comment('SHA-256 hash of the visitor IP address (GDPR compliant)');
            $table->char('country', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code derived from GeoIP');
            $table->string('region', 100)->nullable()->comment('State / province name from GeoIP');
            $table->string('city', 100)->nullable()->comment('City name from GeoIP');
            $table->decimal('latitude', 10, 7)->nullable()->comment('Approximate latitude from GeoIP');
            $table->decimal('longitude', 10, 7)->nullable()->comment('Approximate longitude from GeoIP');

            // Device classification
            $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'bot', 'unknown'])
                ->default('unknown')
                ->comment('Parsed device category from User-Agent');
            $table->string('os', 50)->nullable()->comment('Operating system name from User-Agent');
            $table->string('browser', 50)->nullable()->comment('Browser name from User-Agent');

            // Traffic source
            $table->string('referer', 2048)->nullable()->comment('Full HTTP Referer header value');
            $table->string('referer_domain', 253)->nullable()->comment('Extracted domain from the Referer for grouping');
            $table->text('user_agent')->nullable()->comment('Raw User-Agent header string');

            // Timestamp (no updated_at — rows are immutable)
            $table->timestamp('clicked_at')->useCurrent()->comment('When the redirect occurred');

            // Analytics query indexes
            $table->index(['link_id', 'clicked_at'], 'clicks_link_time_index');
            $table->index('country', 'clicks_country_index');
            $table->index('device_type', 'clicks_device_index');
            $table->index('referer_domain', 'clicks_referer_domain_index');
            $table->index('clicked_at', 'clicks_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
