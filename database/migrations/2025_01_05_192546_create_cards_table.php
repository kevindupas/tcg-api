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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name_fr');
            $table->string('name_en')->nullable();
            $table->string('number');
            $table->string('image');
            $table->foreignId('rarity_type')->constrained('rarities')->onDelete('cascade');
            $table->integer('rarity_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
