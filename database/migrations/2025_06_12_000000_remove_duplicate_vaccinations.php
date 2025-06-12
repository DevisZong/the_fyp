<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Keep the records with null health_care_provider_id and delete the others
        DB::statement("
            DELETE v1 FROM vaccinations v1
            INNER JOIN vaccinations v2
            WHERE v1.child_id = v2.child_id
            AND v1.vaccination_code = v2.vaccination_code
            AND v1.id > v2.id
        ");
    }

    public function down()
    {
        // No down migration needed as we don't want to restore duplicates
    }
};
