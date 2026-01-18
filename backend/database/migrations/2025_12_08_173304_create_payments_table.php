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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade');
            $table->string('payment_gateway'); // midtrans / xendit / tripay
            $table->string('transaction_id')->unique(); // ID dari gateway
            $table->string('payment_method'); // qris / va_bca / va_bri / dll
            $table->decimal('amount', 15, 2); // harus sama dg total_amount
            $table->enum('payment_status', [
                'pending',
                'paid',
                'expired',
                'cancelled',
                'failed'
            ])->default('pending');
            $table->text('payment_url')->nullable(); // link redirect gateway
            $table->json('raw_response')->nullable(); // simpan response API
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
