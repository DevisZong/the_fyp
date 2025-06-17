<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FoodFact;

class FoodFactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */    public function run(): void
    {
        $foodFacts = [
            // Birth to 6 Months
            [
                'age_group_start_month' => 0,
                'age_group_end_month' => 6,
                'message' => 'Maziwa ya mama ndio chakula pekee anachohitaji mtoto katika umri huu. Hakiingizi chochote kingine hata maji. Anza kunyonyesha saa 1 baada ya kuzaliwa. Nyonyesha maziwa ya mwanzo (colostrum) yenye rangi ya njano. Mnyonyeshe kila anapoomba (mara 8-12 kwa siku). Kila mtoto anyonyapo, hakikisha mtoto ananyonya titi moja mpaka maziwa yaishe, kisha umhamishie katika titi lingine. Wakati wa kunyonyesha, muimbie, msemeshe, mpapase na muoneshe vitu vya rangi rangi ili kumwezesha kusikia, kuona, kuhisi na kugusa kwa uhuru.',
            ],

            // 6 to 9 Months
            [
                'age_group_start_month' => 6,
                'age_group_end_month' => 9,
                'message' => 'Anza vyakula vya nyongeza baada ya miezi 6 huku ukiendelea kumnyonyesha maziwa ya mama. Anza na vyakula vya laini kama uji wa mtama, ugali mdogo wa mtama, ndizi zilizoivwa na kupondwa, viazi vikuu vilivoivwa na kupondwa. Mpe chakula mara 2-3 kwa siku. Anza kwa vijiko 2-3 kisha uongeze polepole. Vyakula vya Dodoma: mtama, mahindi, ndizi, viazi vikuu, mchicha, mnanasi. Epuka asali, chumvi nyingi na sukari.',
            ],

            // 9 to 12 Months
            [
                'age_group_start_month' => 9,
                'age_group_end_month' => 12,
                'message' => 'Endelea kuonyesha maziwa ya mama na ongeza vyakula vingine. Mpe chakula mara 3-4 kwa siku. Vyakula vikuu: ugali wa mtama au mahindi, maharage yaliyochemshwa vizuri, mboga za majani (mchicha, sukuma wiki), matunda (ndizi, mananasi, mapapai), mayai yaliyoivwa vizuri. Anza kumpa chakula kilicho na vipande vidogo vidogo. Ni muhimu kumpa mtoto vifaa vinavyomsaidia kujongea (kuinuka, kukaa na kutambaa) na viwe safi, salama na vilivyo katika mazingira yako.',
            ],

            // 1 to 2 Years
            [
                'age_group_start_month' => 12,
                'age_group_end_month' => 24,
                'message' => 'Endelea kunyonyesha mtoto maziwa ya mama. Milo 3 ya vyakula vya familia (kikombe 1 kwa mlo). Mpe mtoto matunda mara 2 kati ya mlo. Msaidie mtoto kujilisha mwenyewe. Vyakula vya bei nafuu vya Dodoma: ugali wa mtama pamoja na maharage na mboga za majani, wali wa mahindi na nyama ya kuku, uji wa mtama na maziwa, ndizi na karanga za kusonga. Hakikisha chakula kina protini, wanga, mboga na matunda.',
            ],

            // 2 to 5 Years
            [
                'age_group_start_month' => 24,
                'age_group_end_month' => 60,
                'message' => 'Tofautisha vyakula kwa rangi na aina. Usimlazimishe kula - pa kiasi cha kutosha. Chakula kutoka kwenye makundi yote 5: wanga (ugali, wali), protini (maharage, nyama, mayai), mboga za majani (mchicha, sukuma wiki), matunda (ndizi, mananasi, mapapai), na mafuta (simsim, karanga). Vyakula vya familia vya Dodoma: ugali wa mtama, maharage, mchicha na nyama; wali wa mahindi na mchuzi wa mboga; uji wa mtama na karanga. Hakikisha chakula ni cha rangi mbalimbali na chenye ladha nzuri.',
            ],

            // Feeding When Sick
            [
                'age_group_start_month' => 0,
                'age_group_end_month' => 60,
                'message' => 'Wakati mtoto ni mgonjwa: Endelea kumnyonyesha maziwa ya mama zaidi ya kawaida. Mpe vyakula vya urahisi kugeuza kama uji wa mtama mzuri, supu ya mboga, maji ya nazi, na matunda yaliyoivwa. Vyakula vya kupona haraka vya Dodoma: uji wa mtama pamoja na karanga za kusonga, supu ya kuku na mboga, maji ya limau na asali kidogo (kwa watoto zaidi ya mwaka 1). Hakikisha anapata maji mengi. Rudi kwa chakula cha kawaida baada ya kupona.',
            ],

            // General Hygiene and Safety
            [
                'age_group_start_month' => 6,
                'age_group_end_month' => 60,
                'message' => 'Usafi wa chakula ni muhimu: Nawa mikono kabla ya kupika na kulisha mtoto. Pika chakula vizuri na kihifadhi katika hali nzuri. Tumia vyombo safi. Maji yawe safi - yachemesha au tumia maji salama. Hifadhi chakula mahali pasipo na vimelea na wadudu. Vyakula visivyoliwa haraka, vitupe ili kuepuka kuharibika.',
            ]
        ];

        foreach ($foodFacts as $fact) {
            FoodFact::create($fact);
        }
    }
}
