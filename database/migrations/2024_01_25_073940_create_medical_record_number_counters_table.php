<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('medical_record_number_counters', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 标识编号类型（如A, CRT, 阿尔法）
            $table->integer('last_number')->default(0); // 当前类型的最后一个编号
            $table->string('prefix')->nullable(); // 编号前缀（如果适用）
            $table->timestamps();
        });

        // 可以在此添加初始数据
        // DB::table('medical_record_number_counters')->insert([
        //     ['type' => 'A', 'last_number' => 0, 'prefix' => 'A-'],
        //     ['type' => 'CRT', 'last_number' => 0, 'prefix' => 'CRT'],
        //     ['type' => '阿尔法', 'last_number' => 0, 'prefix' => '阿尔法']
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_record_number_counters');
    }
};
