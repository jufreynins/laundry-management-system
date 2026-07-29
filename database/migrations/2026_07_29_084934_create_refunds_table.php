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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_reference')->nullable()->unique();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('restrict');
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->foreignId('processed_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id']);
            $table->index(['payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
