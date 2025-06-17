<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Vaccination;
use App\Models\Appointment;
use App\Models\VitaminAndDeworming;
use App\Models\VitaminDewormingReminder;
use Database\Seeders\VaccinationSeeder;
use Database\Seeders\VitaminAndDewormingSeeder;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'childNo',
        'user_id',
        'childName',
        'date_of_birth',
        'gender',
        'birthWeight',
        'birthHeight',
        'fatherName',
        'motherName',
        'birthFacility',
        'birthAttendant',
        'email',
        'phoneNo',
        'address',
        'motherAge'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'address' => 'array',
        'phoneNo' => 'integer',
    ];

    protected $appends = [
        'date_of_birth_formatted',
    ];

    public function getDateOfBirthFormattedAttribute()
    {
        return isset($this->attributes['date_of_birth'])
            ? \Carbon\Carbon::parse($this->attributes['date_of_birth'])->format('Y-m-d')
            : null;
    }

    public function growthRecords()
    {
        return $this->hasMany(GrowthRecords::class, 'child_id');
    }

    public function latestGrowthRecord()
    {
        return $this->hasOne(GrowthRecords::class, 'child_id')->latestOfMany('created_at');
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function vaccination()
    {
        return $this->hasMany(Vaccination::class, 'child_id', 'id');
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class, 'child_id', 'id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'child_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($child) {
            // Generate child number based on birth year if not already set
            if (empty($child->childNo)) {
                $child->childNo = static::generateChildNumber($child->date_of_birth);
            }
        });

        static::created(function ($child) {
            // Create initial growth record with birth measurements
            GrowthRecords::create([
                'child_id' => $child->id,
                'weight' => $child->birthWeight,
                'height' => $child->birthHeight,
                'created_at' => $child->date_of_birth,
                'updated_at' => $child->date_of_birth
            ]);

            // Create vaccination schedule
            $ageInWeeks = $child->date_of_birth->diffInWeeks(now());
            $vaccinationSchedule = [
                0 => ['BCG', 'bOPVO'],
                6 => ['bOPV-1', 'Rota-1', 'DPT-HepB-Hib-1', 'PCV13-1'],
                10 => ['bOPV-2', 'Rota-2', 'DPT-HepB-Hib-2', 'PCV13-2'],
                14 => ['bOPV-3', 'Rota-3', 'DPT-HepB-Hib-3', 'PCV13-3', 'IPV'],
                39 => ['Surua Rubella-1'],
                78 => ['Surua Rubella-3']
            ];

            // Check if this child was created by the factory
            $isFactoryCreated = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $isFromFactory = collect($isFactoryCreated)->contains(function ($trace) {
                return str_contains($trace['file'] ?? '', 'Factory.php') ||
                    str_contains($trace['file'] ?? '', 'ChildSeeder.php') ||
                    str_contains($trace['class'] ?? '', 'Factory');
            });

            foreach (VaccinationSeeder::getVaccinationCodes() as $code) {
                $status = 'inasubiri';
                $vaccineWeek = null;

                // Find which week this vaccine belongs to
                foreach ($vaccinationSchedule as $week => $vaccines) {
                    if (in_array($code, $vaccines)) {
                        $vaccineWeek = $week;
                        break;
                    }
                }

                if ($vaccineWeek !== null) {
                    if ($ageInWeeks > $vaccineWeek + 1) {
                        // If the child is past the vaccination week + grace period
                        if ($isFromFactory) {
                            // For factory-created children: 70% chance of having received the vaccine, 30% chance of having missed it
                            $status = fake()->boolean(70) ? 'imekamilika' : 'amekosa';
                        } else {
                            // For regular children: Always mark as missed if past due
                            $status = 'amekosa';
                        }
                    } elseif ($ageInWeeks >= $vaccineWeek && $isFromFactory) {
                        // Only factory-created children can have random completion in current week
                        $status = fake()->boolean(30) ? 'imekamilika' : 'inasubiri';
                    }
                }

                Vaccination::create([
                    'child_id' => $child->id,
                    'vaccination_code' => $code,
                    'vaccination_no' => $status === 'imekamilika' ? fake()->numerify('VAC####') : null,
                    'Hali' => $status,
                    'health_care_provider_id' => $status === 'imekamilika' ? HealthCareProvider::inRandomOrder()->first()->id : null
                ]);
            }

            // Create vitamin and deworming schedule (10 visits, every 6 months)
            $ageInMonths = $child->date_of_birth->diffInMonths(now());

            for ($i = 1; $i <= 10; $i++) {
                $visitMonth = $i * 6; // 6, 12, 18, 24, ... months
                $scheduledDate = $child->date_of_birth->copy()->addMonths($visitMonth);
                $status = 'inasubiri'; // Default for future visits
                $vitaminA = false;
                $deworming = false;

                // Only mark as missed if child is significantly past the visit date
                if ($ageInMonths > $visitMonth + 1) {
                    // If the child is past the visit month + grace period
                    if ($isFromFactory) {
                        // For factory-created children: 70% chance of having received the vitamins, 30% chance of having missed it
                        $received = fake()->boolean(70);
                        $status = $received ? 'imekamilika' : 'amekosa';
                        $vitaminA = $received;
                        $deworming = $received;
                    } else {
                        // For regular children: Always mark as missed if past due
                        $status = 'amekosa';
                        $vitaminA = false;
                        $deworming = false;
                    }
                } elseif ($ageInMonths >= $visitMonth && $ageInMonths <= $visitMonth + 1 && $isFromFactory) {
                    // Only factory-created children can have random completion in current month window
                    $received = fake()->boolean(30);
                    $status = $received ? 'imekamilika' : 'inasubiri';
                    $vitaminA = $received;
                    $deworming = $received;
                }
                // For all other cases (future visits), keep default: status='inasubiri', vitaminA=false, deworming=false

                VitaminAndDeworming::create([
                    'child_id' => $child->id,
                    'Vitamin_A' => $vitaminA,
                    'Deworming' => $deworming,
                    'status' => $status,
                    'created_at' => $scheduledDate,
                    'updated_at' => $scheduledDate
                ]);
            }

            // Generate appointments for the new child
            self::generateAppointmentsForChild($child);
        });
    }

    /**
     * Generate a sequential child number based on the birth year
     * Format: sequential_number/year (e.g., "1/2025", "13/2025")
     */
    public static function generateChildNumber($dateOfBirth)
    {
        $year = \Carbon\Carbon::parse($dateOfBirth)->year;

        // Get the highest sequential number for children born in the same year
        $latestChild = static::whereYear('date_of_birth', $year)
            ->whereRaw("childNo REGEXP '^[0-9]+/{$year}$'")
            ->orderByRaw('CAST(SUBSTRING_INDEX(childNo, "/", 1) AS UNSIGNED) DESC')
            ->first();

        // Calculate the next sequential number
        $sequentialNumber = 1;
        if ($latestChild && $latestChild->childNo) {
            $currentNumber = (int) explode('/', $latestChild->childNo)[0];
            $sequentialNumber = $currentNumber + 1;
        }

        return $sequentialNumber . '/' . $year;
    }

    /**
     * Generate appointments for a specific child
     */
    public static function generateAppointmentsForChild($child)
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

        $dob = \Carbon\Carbon::parse($child->date_of_birth);
        $maxMonths = 60; // 5 years

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

    public function getVaccinationStatus($vaccinationCode)
    {
        $vaccination = $this->vaccination()->where('vaccination_code', $vaccinationCode)->first();

        if (!$vaccination) {
            return 'pending'; // Or handle the case where the vaccination code is not found
        }

        if ($vaccination->status === 'received') {
            return 'received';
        }

        // Calculate the age in weeks
        $ageInWeeks = $this->getAgeInWeeksAttribute();

        // Get the vaccination schedule
        $vaccinationSchedule = [
            'BCG' => 0,
            'bOPVO' => 0,
            'bOPV-1' => 6,
            'Rota-1' => 6,
            'DPT-HepB-Hib-1' => 6,
            'PCV13-1' => 6,
            'bOPV-2' => 10,
            'Rota-2' => 10,
            'DPT-HepB-Hib-2' => 10,
            'PCV13-2' => 10,
            'bOPV-3' => 14,
            'Rota-3' => 14,
            'DPT-HepB-Hib-3' => 14,
            'PCV13-3' => 14,
            'IPV' => 14,
            'Surua Rubella-1' => 39,
            'Surua Rubella-2' => 78,
        ];

        // Check if the vaccination is in the schedule
        if (!isset($vaccinationSchedule[$vaccinationCode])) {
            return 'unknown'; // Or handle the case where the vaccination code is not in the schedule
        }

        // Get the recommended week for the vaccination
        $recommendedWeek = $vaccinationSchedule[$vaccinationCode];

        // Check if the vaccination is missed
        if ($ageInWeeks > $recommendedWeek + 1) {
            return 'missed';
        }

        return 'pending';
    }

    public function vitaminAndDeworming()
    {
        return $this->hasMany(VitaminAndDeworming::class, 'child_id', 'id');
    }

    public function vitaminDewormingReminders()
    {
        return $this->hasMany(VitaminDewormingReminder::class, 'child_id');
    }

    public function healthCareProviders()
    {
        return $this->belongsToMany(HealthCareProvider::class, 'child_health_care_provider');
    }

    public function vaccinationReminders()
    {
        return $this->hasMany(VaccinationReminder::class, 'child_id');
    }

    public function getAgeInWeeksAttribute()
    {
        return $this->date_of_birth->diffInWeeks(now());
    }

    public function getAgeInMonthsAttribute()
    {
        if (isset($this->attributes['age_in_months'])) {
            return $this->attributes['age_in_months'];
        }
        if (isset($this->date_of_birth)) {
            return $this->date_of_birth->diffInMonths(now());
        }
        if (isset($this->age_in_weeks)) {
            return floor($this->age_in_weeks / 4.345);
        }
        return null;
    }

    /**
     * Calculate the age of the child in years
     */
    public function calculateAge()
    {
        if (!$this->date_of_birth) {
            return 0;
        }

        return \Carbon\Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Get age in months
     */
    public function getAgeInMonths()
    {
        if (!$this->date_of_birth) {
            return 0;
        }

        return \Carbon\Carbon::parse($this->date_of_birth)->diffInMonths(now());
    }
}
