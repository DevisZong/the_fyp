<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_checkup_reminders', function (Blueprint $table) {
            $table->string('reminder_type')->default('child')->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_checkup_reminders', function (Blueprint $table) {
            $table->dropColumn('reminder_type');
        });
    }
};
