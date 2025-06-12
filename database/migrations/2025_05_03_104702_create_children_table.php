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
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('childNo')->unique()->index();
            $table->string('childName');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->decimal('birthWeight', 5, 2)->nullable();
            $table->decimal('birthHeight', 5, 2)->nullable();
            $table->string('fatherName')->nullable();
            $table->string('motherName')->nullable();
            $table->string('birthFacility')->nullable();
            $table->string('birthAttendant')->nullable();
            $table->string('email')->nullable();
            $table->string('phoneNo')->nullable();
            $table->json('address')->nullable();
            $table->integer('motherAge')->nullable();
            $table->unsignedBigInteger('user_id')->unique()->nullable(false);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
            $foreignKeyName = 'vaccinations_child_id_foreign';

            $sql = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName' AND CONSTRAINT_NAME = '$foreignKeyName' AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
            $result = DB::select($sql);

            $hasForeignKey = $result[0]->{'COUNT(*)'} > 0;

            if ($hasForeignKey) {
                $table->dropForeign(['child_id']);
            }
        });
        Schema::dropIfExists('children');
    }
};
