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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('pricing_type');
            $table->decimal('base_price', 10, 2);
            $table->decimal('minimum_charge', 10, 2)->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('rush_eligible')->default(true);
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['category', 'active']);
        });

        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['service_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('services');
    }
};
