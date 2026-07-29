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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->nullable()->unique();
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->boolean('operational_consent')->default(true);
            $table->boolean('marketing_consent')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['location_id', 'name']);
            $table->index(['location_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
