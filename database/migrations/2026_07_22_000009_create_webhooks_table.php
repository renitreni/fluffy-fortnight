<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the webhooks table.
 *
 * Users can subscribe to platform events (e.g., link.clicked, link.created)
 * and receive HTTP POST payloads to a configured URL. Each delivery is signed
 * with an HMAC-SHA256 signature using the per-webhook secret, allowing
 * recipients to verify authenticity.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The user who owns this webhook subscription');
            $table->foreignIdFor(\App\Models\Workspace::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Scope to a workspace; null = fires for all user events');
            $table->string('url', 2048)->comment('The HTTPS endpoint to deliver payloads to');
            $table->json('events')->comment('Array of subscribed event names, e.g. ["link.clicked","link.created"]');
            $table->string('secret')->comment('HMAC-SHA256 signing secret sent in X-Signature-256 header');
            $table->boolean('is_active')->default(true)->comment('Pause webhook without deleting the subscription');
            $table->timestamp('last_triggered_at')->nullable()->comment('When the webhook last successfully fired');
            $table->unsignedSmallInteger('failure_count')->default(0)->comment('Consecutive delivery failures; auto-disables after threshold');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
