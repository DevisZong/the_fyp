<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vaccination_health_care_provider', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vaccination_id');
            $table->unsignedBigInteger('health_care_provider_id');
            $table->timestamps();

            $table->foreign('vaccination_id')->references('id')->on('vaccinations')->onDelete('cascade');
            $table->foreign('health_care_provider_id')->references('id')->on('health_care_providers')->onDelete('cascade');
            $table->unique(['vaccination_id', 'health_care_provider_id'], 'vhcp_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vaccination_health_care_provider');
    }
};
