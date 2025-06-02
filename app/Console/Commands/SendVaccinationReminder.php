<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\VaccinationReminder;
use App\Services\SmsService;

class SendVaccinationReminder extends Command
{

    protected $signature = 'vaccinations:remind';
    protected $description = 'Send vaccination reminders to parents';

    public function handle()
    {
        try {
            $smsService = new SmsService();
            $vaccinationSchedule = $this->getVaccinationSchedule();

            $children = Child::all();

            foreach ($children as $child) {
                $ageInWeeks = $child->age_in_weeks;

                foreach ($vaccinationSchedule as $vaccineAge => $vaccineNames) {
                    // 7 days before
                    if (
                        $ageInWeeks >= $vaccineAge - 1 &&
                        $ageInWeeks < $vaccineAge &&
                        !$child->vaccinationReminders()->where('vaccine_age', $vaccineAge)->where('reminder_type', '7_days_before')->exists()
                    ) {
                        $childName = $child->childName;
                        $vaccinationDate = now()->addWeek()->format('d-m-Y');
                        $message = "Mzazi wa $childName, tunapenda kukukumbusha kuwa mtoto wako anatarajiwa kupata chanjo zifuatazo tarehe $vaccinationDate: " . implode(', ', $vaccineNames) . ". Tafadhali fika kituo cha afya kilicho karibu nawe kwa ajili ya chanjo.";

                        if ($smsService->send($child->phoneNo, $message)) {
                            $child->vaccinationReminders()->create([
                                'vaccine_age' => $vaccineAge,
                                'sent_at' => now(),
                                'reminder_type' => '7_days_before',
                            ]);
                            $this->info("Sent 7-day SMS to {$child->phoneNo} for vaccine age $vaccineAge.");
                        }
                    }
                    // 1 day before
                    if (
                        $ageInWeeks >= $vaccineAge - (1 / 7) &&
                        $ageInWeeks < $vaccineAge &&
                        !$child->vaccinationReminders()->where('vaccine_age', $vaccineAge)->where('reminder_type', '1_day_before')->exists()
                    ) {
                        $childName = $child->childName;
                        $vaccinationDate = now()->addDay()->format('d-m-Y');
                        $message = "Mzazi wa $childName, tunapenda kukukumbusha kuwa mtoto wako anatarajiwa kupata chanjo zifuatazo kesho ($vaccinationDate): " . implode(', ', $vaccineNames) . ". Tafadhali fika kituo cha afya kilicho karibu nawe kwa ajili ya chanjo.";

                        if ($smsService->send($child->phoneNo, $message)) {
                            $child->vaccinationReminders()->create([
                                'vaccine_age' => $vaccineAge,
                                'sent_at' => now(),
                                'reminder_type' => '1_day_before',
                            ]);
                            $this->info("Sent 1-day SMS to {$child->phoneNo} for vaccine age $vaccineAge.");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Error sending vaccination reminders: ' . $e->getMessage());
        }
    }

    private function getVaccinationSchedule(): array
    {
        return [
            6  => [
                'bOPV-1',
                'Rota-1',
                'DPT-HepB-Hib-1',
                'PCV13-1'
            ],
            10 => [
                'bOPV-2',
                'Rota-2',
                'DPT-HepB-Hib-2',
                'PCV13-2'
            ],
            14 => [
                'bOPV-3',
                'Rota-3',
                'DPT-HepB-Hib-3',
                'PCV13-3',
                'IPV'
            ],
            39 => [ // 9 months (approx 39 weeks)
                'Surua Rubella-1'
            ],
            78 => [ // 18 months (approx 78 weeks)
                'Surua Rubella-3'
            ]
        ];
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:send-vaccination-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    /**
     * Execute the console command.
     */
}
