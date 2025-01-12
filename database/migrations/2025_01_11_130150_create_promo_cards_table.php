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
        Schema::create('promo_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name_fr');
            $table->string('name_en')->nullable();
            $table->string('image');
            $table->string('number');
            $table->foreignId('extension_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('rarity_type')->nullable()->constrained('rarities')->onDelete('set null');
            $table->integer('rarity_number')->nullable();
            $table->string('obtention')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_cards');
    }
};
