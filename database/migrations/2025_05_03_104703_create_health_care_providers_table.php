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
        Schema::create('health_care_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('license')->unique();
            $table->string('userRole');
            $table->string('facility');
            $table->string('contact');
            $table->string('gender');
            $table->string('picture')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_care_providers');
    }
};
