<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\MonthlyCheckupReminder;
use App\Services\SmsService;

class SendMonthlyCheckupReminder extends Command
{
    protected $signature = 'checkups:monthly-reminder';
    protected $description = 'Send monthly checkup reminders to parents for children up to 5 years old';

    public function handle()
    {
        $smsService = new SmsService();
        $children = Child::all();
        $maxMonths = 60; // 5 years

        foreach ($children as $child) {
            $ageInMonths = $child->age_in_months;
            if ($ageInMonths < 1 || $ageInMonths > $maxMonths) {
                continue;
            }

            $nextCheckupDate = $child->date_of_birth->copy()->addMonths($ageInMonths);
            $reminderDate = $nextCheckupDate->copy()->subDays(5);

            // Only send if today is the reminder date and not already sent for this month
            if (
                now()->isSameDay($reminderDate) &&
                !MonthlyCheckupReminder::where('child_id', $child->id)->where('month', $ageInMonths)->exists()
            ) {
                $message = "Mpeleke mwanao kwenye kituo cha afya tarehe " . $nextCheckupDate->format('d-m-Y') . " kwa ajili ya uchunguzi wa kila mwezi. Tafadhali hakikisha unafuata ratiba ya chanjo na uchunguzi wa afya.";
                if ($smsService->send($child->phoneNo, $message)) {
                    MonthlyCheckupReminder::create([
                        'child_id' => $child->id,
                        'month' => $ageInMonths,
                        'sent_at' => now(),
                    ]);
                    $this->info("Sent monthly checkup SMS to {$child->phoneNo} for month $ageInMonths.");
                }
            }
        }
    }
}
