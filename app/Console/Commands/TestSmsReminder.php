<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SMSService; // Ensure you have this service for sending SMS

class TestSmsReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-sms-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = '255674960366'; // ← Your test phone number
        $message = 'Ninapenda kukukumbusha kuhusu chanjo ya mtoto wako. Tafadhali tembelea kituo cha afya kilichokaribu kwa maelezo zaidi.'; // ← Your test message

        $smsService = new SMSService(); // Create an instance of SMSService

        if ($smsService->send($phone, $message)) {
            $this->info("SMS sent successfully to $phone");
        } else {
            $this->error("Failed to send SMS to $phone");
        }
    
    }
}
