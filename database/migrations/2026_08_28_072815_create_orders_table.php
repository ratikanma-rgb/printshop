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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // ลูกค้าที่สั่งงาน
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // เลขที่รายการและเลขคิว
            $table->string('order_no')->unique();
            $table->string('queue_no')->nullable();

            // ราคารวม
            $table->decimal('total_price', 10, 2)->default(0);

            // สถานะงาน
            $table->string('status')->default('pending');

            // สถานะการชำระเงิน
            $table->string('payment_status')->default('unpaid');

            // หมายเหตุของลูกค้า
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};