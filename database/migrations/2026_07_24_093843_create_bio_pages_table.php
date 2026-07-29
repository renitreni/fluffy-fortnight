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
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The user who created this bio page');
            $table->string('alias', 255)->unique()->comment('Unique slug used in the public URL');
            $table->string('title', 255)->comment('Page title');
            $table->text('description')->nullable()->comment('Bio description');
            $table->string('theme', 50)->default('light')->comment('Selected theme');
            $table->string('profile_image_path', 255)->nullable()->comment('Path to profile image');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bio_pages');
    }
};
