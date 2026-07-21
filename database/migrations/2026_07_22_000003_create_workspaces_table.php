<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the workspaces table.
 *
 * Workspaces are organizational units that group links, custom domains,
 * team members, and API keys. A user can own multiple workspaces (subject
 * to their subscription plan limit) and belong to others as a member.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class, 'owner_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('The user who owns and administers this workspace');
            $table->string('name', 100)->comment('Display name of the workspace');
            $table->string('slug', 100)->unique()->comment('URL-friendly unique identifier');
            $table->string('logo')->nullable()->comment('Path or URL to the workspace logo');
            $table->unsignedSmallInteger('custom_domain_limit')
                ->default(1)
                ->comment('Maximum custom domains allowed for this workspace');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
