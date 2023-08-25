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
        Schema::create('girl_who_growth_standards_heights', function (Blueprint $table) {
            $table->id();
            $table->integer('age_month')->nullable();
            $table->decimal('height_minus_3sd', 10, 2)->nullable();
            $table->decimal('height_minus_2sd', 10, 2)->nullable();
            $table->decimal('height_minus_1sd', 10, 2)->nullable();
            $table->decimal('height_0sd', 10, 2)->nullable();
            $table->decimal('height_plus_1sd', 10, 2)->nullable();
            $table->decimal('height_plus_2sd', 10, 2)->nullable();
            $table->decimal('height_plus_3sd', 10, 2)->nullable();
            $table->decimal('weight_minus_3sd', 10, 2)->nullable();
            $table->decimal('weight_minus_2sd', 10, 2)->nullable();
            $table->decimal('weight_minus_1sd', 10, 2)->nullable();
            $table->decimal('weight_0sd', 10, 2)->nullable();
            $table->decimal('weight_plus_1sd', 10, 2)->nullable();
            $table->decimal('weight_plus_2sd', 10, 2)->nullable();
            $table->decimal('weight_plus_3sd', 10, 2)->nullable();
            $table->decimal('head_circumference_minus_3sd', 10, 2)->nullable();
            $table->decimal('head_circumference_minus_2sd', 10, 2)->nullable();
            $table->decimal('head_circumference_minus_1sd', 10, 2)->nullable();
            $table->decimal('head_circumference_0sd', 10, 2)->nullable();
            $table->decimal('head_circumference_plus_1sd', 10, 2)->nullable();
            $table->decimal('head_circumference_plus_2sd', 10, 2)->nullable();
            $table->decimal('head_circumference_plus_3sd', 10, 2)->nullable();
            $table->decimal('bmi_minus_3sd', 10, 2)->nullable();
            $table->decimal('bmi_minus_2sd', 10, 2)->nullable();
            $table->decimal('bmi_minus_1sd', 10, 2)->nullable();
            $table->decimal('bmi_0sd', 10, 2)->nullable();
            $table->decimal('bmi_plus_1sd', 10, 2)->nullable();
            $table->decimal('bmi_plus_2sd', 10, 2)->nullable();
            $table->decimal('bmi_plus_3sd', 10, 2)->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('girl_who_growth_standards_heights');
    }
};
