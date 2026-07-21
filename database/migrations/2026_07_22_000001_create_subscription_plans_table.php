<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the subscription_plans table.
 *
 * Stores the available subscription tiers (free, pro, enterprise) and their
 * corresponding limits, Stripe price IDs, and JSON feature flags. This table
 * acts as a configuration source for feature-gating middleware.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('Machine-readable key: free, pro, enterprise');
            $table->string('display_name', 100)->comment('Human-readable label shown in the UI');
            $table->json('features')->nullable()->comment('JSON object of feature flag keys and values');
            $table->decimal('price_monthly', 10, 2)->nullable()->comment('Monthly billing price in USD');
            $table->decimal('price_yearly', 10, 2)->nullable()->comment('Yearly billing price in USD');
            $table->string('stripe_monthly_price_id')->nullable()->comment('Stripe Price ID for monthly billing');
            $table->string('stripe_yearly_price_id')->nullable()->comment('Stripe Price ID for yearly billing');
            $table->unsignedInteger('max_links')->default(10)->comment('Maximum number of active links; 0 = unlimited');
            $table->unsignedSmallInteger('max_workspaces')->default(1)->comment('Maximum workspaces a user on this plan can create');
            $table->unsignedSmallInteger('max_custom_domains')->default(0)->comment('Maximum custom domains allowed');
            $table->boolean('is_active')->default(true)->comment('Hide from plan selection without deleting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
