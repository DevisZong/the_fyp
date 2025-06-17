<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\FoodFact;
use App\Services\SmsService;
use Carbon\Carbon;

class SendFoodFactNotifications extends Command
{
    protected $signature = 'foodfacts:send-notifications';
    protected $description = 'Send monthly food fact notifications to parents based on child age group';
    public function handle()
    {
        $smsService = new SmsService();
        $children = Child::all();

        foreach ($children as $child) {
            $ageInMonths = $child->age_in_months;

            $foodFact = FoodFact::getForAge($ageInMonths);

            if ($foodFact) {
                $message = $foodFact->message;
                if ($smsService->send($child->phoneNo, $message)) {
                    $this->info("Sent food fact SMS to {$child->phoneNo} for age $ageInMonths months ({$foodFact->age_group}).");
                } else {
                    $this->error("Failed to send SMS to {$child->phoneNo}");
                }
            } else {
                $this->warn("No food fact found for child {$child->id} with age $ageInMonths months.");
            }
        }

        $this->info('Food fact notifications processing completed.');
    }
}
