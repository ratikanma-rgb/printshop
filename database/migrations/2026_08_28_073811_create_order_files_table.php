<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_files', function (Blueprint $table) {
            $table->id();

            // อ้างอิงคำสั่งงาน
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // ชื่อไฟล์เดิมที่ลูกค้าอัปโหลด
            $table->string('original_name');

            // ชื่อไฟล์ที่ระบบบันทึก
            $table->string('stored_name');

            // ตำแหน่งเก็บไฟล์
            $table->string('file_path');

            // ประเภทไฟล์ เช่น application/pdf
            $table->string('mime_type')->nullable();

            // นามสกุลไฟล์ เช่น pdf, docx, jpg
            $table->string('extension')->nullable();

            // ขนาดไฟล์เป็น byte
            $table->unsignedBigInteger('file_size')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_files');
    }
};