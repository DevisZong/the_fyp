<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->string('appointment_type');
            $table->date('appointment_date');
            $table->string('appointment_name');
            $table->boolean('notified_parent_5_days')->default(false);
            $table->boolean('notified_parent_1_day')->default(false);
            $table->boolean('notified_provider')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
