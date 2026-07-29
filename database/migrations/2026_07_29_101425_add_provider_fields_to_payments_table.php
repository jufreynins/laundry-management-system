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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('method');
            $table->string('provider_transaction_id')->nullable()->unique()->after('provider');
            $table->string('provider_customer_id')->nullable()->after('provider_transaction_id');
            $table->string('payment_method_brand')->nullable()->after('provider_customer_id');
            $table->string('last_four', 4)->nullable()->after('payment_method_brand');
            $table->string('receipt_url')->nullable()->after('last_four');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'provider', 'provider_transaction_id', 'provider_customer_id',
                'payment_method_brand', 'last_four', 'receipt_url',
            ]);
        });
    }
};
