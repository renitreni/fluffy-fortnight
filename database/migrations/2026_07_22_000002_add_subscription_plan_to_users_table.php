<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds subscription_plan_id foreign key to the users table.
 *
 * Separated from the users table creation so it can run after
 * subscription_plans is seeded, avoiding a chicken-and-egg constraint issue.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\SubscriptionPlan::class)
                ->nullable()
                ->after('is_active')
                ->constrained()
                ->nullOnDelete()
                ->comment('Active subscription plan; null defaults to free tier logic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(\App\Models\SubscriptionPlan::class);
        });
    }
};
