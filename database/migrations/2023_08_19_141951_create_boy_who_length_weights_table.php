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
        Schema::create('boy_who_length_weights', function (Blueprint $table) {
            $table->id();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('weight_minus_3sd', 10, 2)->nullable();
            $table->decimal('weight_minus_2sd', 10, 2)->nullable();
            $table->decimal('weight_minus_1sd', 10, 2)->nullable();
            $table->decimal('weight_0sd', 10, 2)->nullable();
            $table->decimal('weight_plus_1sd', 10, 2)->nullable();
            $table->decimal('weight_plus_2sd', 10, 2)->nullable();
            $table->decimal('weight_plus_3sd', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boy_who_length_weights');
    }
};
