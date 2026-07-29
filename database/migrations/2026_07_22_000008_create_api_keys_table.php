<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the api_keys table.
 *
 * Stores hashed API keys for programmatic access. The raw key is shown
 * once to the user on creation and never stored in plain text. Only the
 * SHA-256 hash (key_hash) and the first 8 characters (key_prefix) are
 * persisted to allow identification without exposing the secret.
 *
 * Abilities follow the Laravel Sanctum token ability convention as a JSON
 * array (e.g., ["links:read", "links:create", "analytics:read"]).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The user who owns this API key');
            $table->foreignIdFor(Workspace::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Scope this key to a specific workspace; null = personal key');
            $table->string('name', 100)->comment('Human-readable label to identify the key in the dashboard');
            $table->string('key_hash', 64)->unique()->comment('SHA-256 hash of the raw API key for verification');
            $table->string('key_prefix', 8)->comment('First 8 chars of the raw key shown in the UI for identification');
            $table->json('abilities')->nullable()->comment('Scoped permissions array, e.g. ["links:read","analytics:read"]');
            $table->timestamp('last_used_at')->nullable()->comment('When this key last authenticated a request');
            $table->timestamp('expires_at')->nullable()->comment('Expiry timestamp; null = never expires');
            $table->boolean('is_active')->default(true)->comment('Revoke access without deleting the key record');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
