<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildHealthCareProviderTable extends Migration
{
    public function up()
    {
        Schema::create('child_health_care_provider', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->unsignedBigInteger('health_care_provider_id');
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('health_care_provider_id')->references('id')->on('health_care_providers')->onDelete('cascade');
            $table->unique(['child_id', 'health_care_provider_id'], 'child_hcp_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('child_health_care_provider');
    }
}