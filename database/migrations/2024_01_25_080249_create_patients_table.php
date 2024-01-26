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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 姓名
            $table->string('gender')->nullable(); // 性别
            $table->string('phone')->nullable(); // 电话号码
            $table->string('resident_id_number')->nullable();//身份证号码
            $table->timestamps(); // 创建时间和更新时间
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
