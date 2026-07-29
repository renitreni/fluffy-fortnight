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
        Schema::create('bio_page_link', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\BioPage::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Link::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('display_order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bio_page_link');
    }
};
