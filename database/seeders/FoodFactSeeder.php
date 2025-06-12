<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FoodFact;

class FoodFactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $foodFacts = [
            [
                'age_group_start_month' => 0,
                'age_group_end_month' => 6,
                'message' => 'Kwa watoto wa miezi 0 hadi 6, chakula bora ni maziwa ya mama pekee. Wazazi wanashauriwa kuhakikisha mtoto anapata maziwa ya kutosha na kuepuka chakula kingine chochote. Dodoma ina chakula cha asili kama ugali wa mtama na mboga mboga za majani ambazo zinaweza kuanzishwa baada ya miezi 6.',
            ],
            [
                'age_group_start_month' => 7,
                'age_group_end_month' => 12,
                'message' => 'Kwa watoto wa miezi 7 hadi 12, unaweza kuanzisha chakula laini kama ugali wa mtama, maharage yaliyochemshwa, na mboga mboga za majani. Hakikisha mtoto anaendelea kunyonyesha maziwa ya mama.',
            ],
            [
                'age_group_start_month' => 13,
                'age_group_end_month' => 24,
                'message' => 'Kwa watoto wa miaka 1 hadi 2, chakula kinapaswa kuwa mchanganyiko wa wanga, protini, na mboga. Chakula cha Dodoma kama ugali wa mtama, maharage, mboga za majani, na matunda ni bora kwa ukuaji wa mtoto.',
            ],
            [
                'age_group_start_month' => 25,
                'age_group_end_month' => 60,
                'message' => 'Kwa watoto wa miaka 2 hadi 5, chakula kinapaswa kuwa chenye virutubisho vyote muhimu. Wazazi wanashauriwa kutoa chakula cha mchanganyiko wa wanga, protini, mboga, na matunda ya asili ya Dodoma.',
            ],
        ];

        foreach ($foodFacts as $fact) {
            FoodFact::create($fact);
        }
    }
}
