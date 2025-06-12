<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\Appointment;
use Carbon\Carbon;

class GenerateAppointments extends Command
{
    protected $signature = 'appointments:generate';
    protected $description = 'Generate monthly visit and vaccination appointments for children';

    public function handle()
    {
        $vaccinationSchedule = [
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
                'Surua Rubella-2'
            ]
        ];

        $children = Child::all();
        $maxMonths = 60; // 5 years

        foreach ($children as $child) {
            $dob = Carbon::parse($child->date_of_birth);

            // Generate monthly visit appointments
            for ($month = 1; $month <= $maxMonths; $month++) {
                $appointmentDate = $dob->copy()->addMonths($month);
                if ($appointmentDate->isFuture()) {
                    Appointment::firstOrCreate([
                        'child_id' => $child->id,
                        'appointment_type' => 'monthly_visit',
                        'appointment_date' => $appointmentDate->toDateString(),
                        'appointment_name' => 'Monthly Visit',
                    ]);
                }
            }

            // Generate vaccination appointments
            foreach ($vaccinationSchedule as $weekAge => $vaccines) {
                $appointmentDate = $dob->copy()->addWeeks($weekAge);
                if ($appointmentDate->isFuture()) {
                    foreach ($vaccines as $vaccineName) {
                        Appointment::firstOrCreate([
                            'child_id' => $child->id,
                            'appointment_type' => 'vaccination',
                            'appointment_date' => $appointmentDate->toDateString(),
                            'appointment_name' => $vaccineName,
                        ]);
                    }
                }
            }
        }

        $this->info('Appointments generated successfully.');
    }
}
