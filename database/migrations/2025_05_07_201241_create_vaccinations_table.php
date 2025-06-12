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
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('health_care_provider_id')->nullable();
            $table->foreign('health_care_provider_id')->references('id')->on('health_care_providers')->onDelete('set null');
            $table->string('vaccination_code');
            $table->string('vaccination_no')->nullable();
            $table->enum('Hali', ['inasubiri', 'imekamilika', 'amekosa'])->default('inasubiri');
            $table->timestamps();

            $table->index('vaccination_code');
            $table->index('child_id');
        });
        if (Schema::hasColumn('vaccinations', 'status')) {
            Schema::table('vaccinations', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vaccinations', function (Blueprint $table) {
            $tableName = 'vaccinations';
            $childForeignKeyName = 'vaccinations_child_id_foreign';
            $healthCareProviderForeignKeyName = 'vaccinations_health_care_provider_id_foreign';

            $sqlChild = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName' AND CONSTRAINT_NAME = '$childForeignKeyName' AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
            $resultChild = DB::select($sqlChild);
            $hasChildForeignKey = $resultChild[0]->{'COUNT(*)'} > 0;

            $sqlHealthCareProvider = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName' AND CONSTRAINT_NAME = '$healthCareProviderForeignKeyName' AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
            $resultHealthCareProvider = DB::select($sqlHealthCareProvider);
            $hasHealthCareProviderForeignKey = $resultHealthCareProvider[0]->{'COUNT(*)'} > 0;

            if ($hasChildForeignKey) {
                try {
                    $table->dropForeign(['child_id']);
                } catch (\Exception $e) {
                    // Handle the exception (e.g., log it or ignore it)
                }
            }

            if ($hasHealthCareProviderForeignKey) {
                try {
                    $table->dropForeign(['health_care_provider_id']);
                } catch (\Exception $e) {
                    // Handle the exception (e.g., log it or ignore it)
                }
            }
        });
         if (Schema::hasTable('vaccinations')) {
            Schema::dropIfExists('vaccinations');
        }
    }
};
