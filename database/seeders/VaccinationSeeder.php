<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vaccination;
use App\Models\Child;

class VaccinationSeeder extends Seeder
{
    private $vaccinationCodes = [
        'BCG',
        'bOPVO',
        'BCG-2', // From "Measure-motometer" in your table
        'bOPV-1',
        'Rota-1',
        'DPT-HepB-Hib-1',
        'PCV13-1',
        'bOPV-2',
        'Rota-2',
        'DPT-HepB-Hib-2',
        'PCV13-2',
        'bOPV-3',
        'Rota-3',
        'DPT-HepB-Hib-3',
        'PCV13-3',
        'IPV',
        'Surua Rubella-1',
        'Surua Rubella-2'
    ];
    /**
     * Run the database seeds.
     */
    // database/seeders/VaccinationSeeder.php
 public function run()
    {
        // Create vaccination records for existing children
        Child::each(function ($child) {
            $this->createVaccinationRecords($child->id);
        });
    }

        public static function getVaccinationCodes()
    {
        return (new self)->vaccinationCodes;
    }

    public function createVaccinationRecords($childId)
    {
        foreach ($this->vaccinationCodes as $code) {
            Vaccination::firstOrCreate([
                'child_id' => $childId,
                'vaccination_code' => $code
            ], [
                'vaccination_no' => null,
                'status' => false
            ]);
        }
    }
}
