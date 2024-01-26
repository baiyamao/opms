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
        Schema::create('medical_record_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 标识编号类型（如A, CRT, 阿尔法）
            $table->integer('number'); // 当前类型的最后一个编号
            $table->unsignedBigInteger('patient_id'); // 添加外键字段
            $table->foreign('patient_id')->references('id')->on('patients'); // 设置外键约束
            $table->timestamps();
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
