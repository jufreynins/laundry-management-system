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
        Schema::table('orders', function (Blueprint $table) {
            $table->json('garment_flags')->nullable()->after('stain_notes');
            $table->unsignedInteger('customer_declared_item_count')->nullable()->after('item_count');
            $table->boolean('customer_acknowledged')->default(false)->after('garment_flags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['garment_flags', 'customer_declared_item_count', 'customer_acknowledged']);
        });
    }
};
