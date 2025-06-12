<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        Schema::table('vaccinations', function (Blueprint $table) {
            $tableName = 'vaccinations';
            $foreignKeyName = 'vaccinations_health_care_provider_id_foreign';

            $sql = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName' AND CONSTRAINT_NAME = '$foreignKeyName' AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
            $result = DB::select($sql);

            $hasForeignKey = $result[0]->{'COUNT(*)'} > 0;

            if ($hasForeignKey) {
                $table->dropForeign(['health_care_provider_id']);
            }
        });
        Schema::dropIfExists('health_care_providers');
    }
};
