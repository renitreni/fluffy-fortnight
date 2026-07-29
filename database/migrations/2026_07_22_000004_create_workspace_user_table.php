<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the workspace_user pivot table.
 *
 * Manages the many-to-many relationship between users and workspaces,
 * storing the user's role within each workspace. Roles are enforced by
 * the RBAC middleware introduced in Day 25.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Workspace::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The workspace this membership belongs to');
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The member user');
            $table->enum('role', ['admin', 'editor', 'viewer'])
                ->default('viewer')
                ->comment('Permission level within the workspace');
            $table->timestamp('joined_at')->nullable()->comment('When the user accepted the invitation');
            $table->timestamps();

            // A user can only have one role per workspace
            $table->unique(['workspace_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_user');
    }
};
