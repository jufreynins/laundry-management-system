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
            $table->string('payment_reference')->nullable()->unique();
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            $table->string('method');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('reference_note')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('void_reason')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['order_id']);
            $table->index(['location_id', 'created_at']);
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
