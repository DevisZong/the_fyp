<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\VitaminDewormingReminder;
use App\Services\SmsService;
use Database\Seeders\VitaminAndDewormingSeeder;

class SendVitaminDewormingReminder extends Command
{
    protected $signature = 'vitamin-deworming:remind';
    protected $description = 'Send vitamin and deworming reminders to parents';

    public function handle()
    {
        try {
            $smsService = new SmsService();
            $vitaminSchedule = VitaminAndDewormingSeeder::getVitaminSchedule();

            $children = Child::all();

            foreach ($children as $child) {
                $ageInMonths = $child->age_in_months;

                foreach ($vitaminSchedule as $visitMonth => $visitName) {
                    // 7 days before (approximately 1 week = 0.25 months)
                    if (
                        $ageInMonths >= $visitMonth - 0.25 &&
                        $ageInMonths < $visitMonth &&
                        !$child->vitaminDewormingReminders()->where('visit_month', $visitMonth)->where('reminder_type', '7_days_before')->exists()
                    ) {
                        $childName = $child->childName;
                        $visitDate = $child->date_of_birth->copy()->addMonths($visitMonth)->format('d-m-Y');
                        $message = "Mzazi wa $childName, tunapenda kukukumbusha kuwa mtoto wako anatarajiwa kupata vitamini A na dawa za minyoo tarehe $visitDate ($visitName). Tafadhali fika kituo cha afya kilicho karibu nawe.";

                        if ($smsService->send($child->phoneNo, $message)) {
                            $child->vitaminDewormingReminders()->create([
                                'visit_month' => $visitMonth,
                                'sent_at' => now(),
                                'reminder_type' => '7_days_before',
                            ]);
                            $this->info("Sent 7-day vitamin/deworming SMS to {$child->phoneNo} for {$visitName}.");
                        }
                    }

                    // 1 day before
                    if (
                        $ageInMonths >= $visitMonth - (1 / 30) &&
                        $ageInMonths < $visitMonth &&
                        !$child->vitaminDewormingReminders()->where('visit_month', $visitMonth)->where('reminder_type', '1_day_before')->exists()
                    ) {
                        $childName = $child->childName;
                        $visitDate = $child->date_of_birth->copy()->addMonths($visitMonth)->format('d-m-Y');
                        $message = "Mzazi wa $childName, tunapenda kukukumbusha kuwa mtoto wako anatarajiwa kupata vitamini A na dawa za minyoo kesho ($visitDate) - $visitName. Tafadhali fika kituo cha afya kilicho karibu nawe.";

                        if ($smsService->send($child->phoneNo, $message)) {
                            $child->vitaminDewormingReminders()->create([
                                'visit_month' => $visitMonth,
                                'sent_at' => now(),
                                'reminder_type' => '1_day_before',
                            ]);
                            $this->info("Sent 1-day vitamin/deworming SMS to {$child->phoneNo} for {$visitName}.");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Error sending vitamin/deworming reminders: ' . $e->getMessage());
        }
    }
}
