<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('click_hourly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Link::class)->constrained()->cascadeOnDelete();
            $table->timestamp('hour')->comment('The start of the hour for this summary');
            $table->char('country', 2)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('referer_domain', 253)->nullable();
            $table->unsignedInteger('clicks')->default(0);

            // Unique index to allow safe upserts / increments
            $table->unique([
                'link_id', 'hour', 'country', 'device_type', 'os', 'browser', 'referer_domain'
            ], 'click_hour_summary_unique');

            // Index for fast time-series queries on the dashboard
            $table->index(['link_id', 'hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_hourly_summaries');
    }
};
