<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Vaccination;
use Database\Seeders\VaccinationSeeder;

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

    protected static function booted()
    {
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
        });
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
}
