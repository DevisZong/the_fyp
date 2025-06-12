<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\SmsService;
use Carbon\Carbon;

class SendAppointmentNotifications extends Command
{
    protected $signature = 'appointments:send-notifications';
    protected $description = 'Send notifications for upcoming appointments (monthly checkup and vaccination) to parents only';

    public function handle()
    {
        $smsService = new SmsService();
        $today = Carbon::today();

        // Notify 5 days before appointment to parent if not notified
        $appointments5Days = Appointment::whereDate('appointment_date', '=', $today->copy()->addDays(5))
            ->whereIn('appointment_type', ['monthly_visit', 'vaccination'])
            ->where('notified_parent_5_days', false)
            ->get();

        foreach ($appointments5Days as $appointment) {
            $child = $appointment->child;
            $dateFormatted = $appointment->appointment_date->format('d-m-Y');
            $messageParent = "Kumbuka kupeleka mtoto wako kwenye kituo cha afya tarehe $dateFormatted kwa ajili ya: {$appointment->appointment_name}.";

            if ($smsService->send($child->phoneNo, $messageParent)) {
                $appointment->notified_parent_5_days = true;
                $appointment->save();
                $this->info("Sent 5-day notification SMS to {$child->phoneNo} for appointment ID: {$appointment->id}");
            }
        }

        // Notify 1 day before appointment to parent if not notified
        $appointments1Day = Appointment::whereDate('appointment_date', '=', $today->copy()->addDay())
            ->whereIn('appointment_type', ['monthly_visit', 'vaccination'])
            ->where('notified_parent_1_day', false)
            ->get();

        foreach ($appointments1Day as $appointment) {
            $child = $appointment->child;
            $dateFormatted = $appointment->appointment_date->format('d-m-Y');
            $messageParent = "Kumbuka kupeleka mtoto wako kwenye kituo cha afya kesho ($dateFormatted) kwa ajili ya: {$appointment->appointment_name}.";

            if ($smsService->send($child->phoneNo, $messageParent)) {
                $appointment->notified_parent_1_day = true;
                $appointment->save();
                $this->info("Sent 1-day notification SMS to {$child->phoneNo} for appointment ID: {$appointment->id}");
            }
        }
    }
}
