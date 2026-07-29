<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the custom_domains table.
 *
 * Stores user-owned domains that can be used as branded short link hosts
 * (e.g., link.mybrand.com). Domain ownership is verified via a DNS TXT
 * record containing the verification_token. SSL status is managed
 * asynchronously by a background job.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Workspace::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Workspace this domain belongs to; null if personally owned');
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The user who added this domain');
            $table->string('domain', 253)->unique()->comment('Fully-qualified domain name (e.g. link.brand.com)');
            $table->boolean('is_verified')->default(false)->comment('Whether DNS ownership has been confirmed');
            $table->timestamp('verified_at')->nullable()->comment('Timestamp when domain was successfully verified');
            $table->string('verification_token')->nullable()->comment('Token placed in DNS TXT record for ownership proof');
            $table->enum('ssl_status', ['pending', 'active', 'failed'])
                ->default('pending')
                ->comment('SSL certificate provisioning state');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_domains');
    }
};
