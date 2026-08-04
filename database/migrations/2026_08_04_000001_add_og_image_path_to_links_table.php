<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add og_image_path column to links table.
 *
 * Stores the path to an uploaded OpenGraph image that will be served
 * when social media crawlers (Facebook, Twitter, LinkedIn, etc.) visit
 * the short URL. This allows custom link previews on social platforms.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('og_image_path', 255)->nullable()->after('description')
                ->comment('Path to uploaded OG image for social preview cards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('og_image_path');
        });
    }
};
