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
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('childNo')->unique()->index();
            $table->string('childName');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->decimal('birthWeight', 5, 2)->nullable();
            $table->string('fatherName')->nullable();
            $table->string('motherName')->nullable();
            $table->string('birthFacility')->nullable();
            $table->string('birthAttendant')->nullable();
            $table->string('email')->nullable();
            $table->string('phoneNo')->nullable();
            $table->json('address')->nullable();
            $table->integer('motherAge')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('health_care_provider_id')->nullable()->constrained('health_care_providers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
