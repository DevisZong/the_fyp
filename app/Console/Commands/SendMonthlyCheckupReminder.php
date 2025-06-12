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
            $monthlyReminderDate = $nextCheckupDate->copy()->subDays(5);
            $parentReminderDate = $nextCheckupDate->copy()->subDays(1);

            // Send monthly reminder 5 days before appointment if not already sent
            if (
                now()->isSameDay($monthlyReminderDate) &&
                !MonthlyCheckupReminder::where('child_id', $child->id)
                    ->where('month', $ageInMonths)
                    ->where('reminder_type', 'child')
                    ->exists()
            ) {
                $message = "Mpeleke mwanao kwenye kituo cha afya tarehe " . $nextCheckupDate->format('d-m-Y') . " kwa ajili ya uchunguzi wa kila mwezi. Tafadhali hakikisha unafuata ratiba ya chanjo na uchunguzi wa afya.";
                if ($smsService->send($child->phoneNo, $message)) {
                    MonthlyCheckupReminder::create([
                        'child_id' => $child->id,
                        'month' => $ageInMonths,
                        'sent_at' => now(),
                        'reminder_type' => 'child',
                    ]);
                    $this->info("Sent monthly checkup SMS to {$child->phoneNo} for month $ageInMonths.");
                }
            }

            // Send parent reminder 1 day before appointment if not already sent
            if (
                now()->isSameDay($parentReminderDate) &&
                !MonthlyCheckupReminder::where('child_id', $child->id)
                    ->where('month', $ageInMonths)
                    ->where('reminder_type', 'parent')
                    ->exists()
            ) {
                $parentMessage = "Kumbuka kupeleka mtoto wako kwenye kituo cha afya kesho tarehe " . $nextCheckupDate->format('d-m-Y') . " kwa ajili ya uchunguzi wa kila mwezi.";
                if ($smsService->send($child->phoneNo, $parentMessage)) {
                    MonthlyCheckupReminder::create([
                        'child_id' => $child->id,
                        'month' => $ageInMonths,
                        'sent_at' => now(),
                        'reminder_type' => 'parent',
                    ]);
                    $this->info("Sent parent reminder SMS to {$child->phoneNo} for month $ageInMonths.");
                }
            }
        }
    }
}
