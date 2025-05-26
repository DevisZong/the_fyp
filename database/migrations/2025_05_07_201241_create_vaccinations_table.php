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
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->foreignId('health_care_provider_id')->references('id')->on('health_care_providers');
            $table->string('vaccination_code');
            $table->string('vaccination_no')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
            
            $table->index('vaccination_code');
            $table->index('child_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};
