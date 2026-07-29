<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the blocked_urls table.
 *
 * A denylist of URLs that must not be shortened. Entries are added either
 * automatically (via Safe Browsing / PhishTank API integration, Day 10)
 * or manually by administrators. Lookups are performed against url_hash
 * (SHA-256 of the normalized URL) for O(1) index reads without storing
 * raw URLs in a unique index (which would be truncated by MySQL for long URLs).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blocked_urls', function (Blueprint $table) {
            $table->id();
            $table->string('url_hash', 64)->unique()->comment('SHA-256 hash of the normalized URL for fast O(1) denylist lookup');
            $table->text('url')->comment('Original URL for human review in the admin panel');
            $table->enum('reason', ['malicious', 'phishing', 'spam', 'manual'])
                ->comment('Classification of why this URL is blocked');
            $table->string('source', 50)->nullable()->comment('Detection source, e.g. "google_safe_browsing", "phishtank", "admin"');
            $table->foreignIdFor(User::class, 'blocked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin user who manually added this entry; null if auto-detected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_urls');
    }
};
