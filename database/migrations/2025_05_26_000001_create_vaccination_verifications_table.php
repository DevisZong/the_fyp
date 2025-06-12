<?php
// database/migrations/2025_05_26_000001_create_vaccination_verifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('vaccination_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->unsignedBigInteger('health_care_provider_id');
            $table->string('vaccination_code', 50);
            $table->json('vaccination_nos')->nullable();
            $table->string('verification_code', 10);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('health_care_provider_id')->references('id')->on('health_care_providers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vaccination_verifications');
    }
};
