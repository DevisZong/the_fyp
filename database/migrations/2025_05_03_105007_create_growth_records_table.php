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
        Schema::create('growth_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id'); // Add this line
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade'); // Add this line            php artisan migrate:refresh
            $table->unsignedBigInteger('health_care_provider_id')->nullable();
            $table->foreign('health_care_provider_id')->references('id')->on('health_care_providers'); // Add this line
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_records');
    }
};
