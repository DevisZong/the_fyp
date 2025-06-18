<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->text('details')->nullable()->after('action');
            $table->string('ip_address', 45)->nullable()->after('details');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('type')->default('info')->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['details', 'ip_address', 'user_agent', 'type']);
        });
    }
};
