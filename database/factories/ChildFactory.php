<?php

namespace Database\Factories;

use App\Models\Child;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChildFactory extends Factory
{
    protected $model = Child::class;

    /**
     * Swahili names for children
     */
    private $swahiliChildNames = [
        // Male names
        'Amani', 'Baraka', 'Daudi', 'Erick', 'Faraji', 'Hamisi', 'Ibrahim', 'Juma', 'Kelvin', 'Lameck',
        'Mwalimu', 'Nuru', 'Omari', 'Paulo', 'Rashidi', 'Salim', 'Tumaini', 'Upendo', 'Venance', 'Waziri',
        'Yusuph', 'Zuberi', 'Abdallah', 'Bakari', 'Chande', 'Dotto', 'Elias', 'Fidelis', 'Goodluck', 'Hassan',
        'Idris', 'Japhet', 'Kalunde', 'Lipemba', 'Mhina', 'Ndunguru', 'Othman', 'Pili', 'Ramadhani', 'Shaban',
        'Tabu', 'Ukweli', 'Vicent', 'Wambura', 'Yahya', 'Zakayo',
        
        // Female names
        'Asha', 'Bahati', 'Dorcas', 'Esther', 'Fatuma', 'Grace', 'Hawa', 'Imelda', 'Jokate', 'Khadija',
        'Latifa', 'Mwajuma', 'Neema', 'Olivia', 'Pendo', 'Rahma', 'Sara', 'Tumaini', 'Upendo', 'Vaileth',
        'Wema', 'Yusta', 'Zuhura', 'Amina', 'Beatrice', 'Christina', 'Deborah', 'Elizabeth', 'Farida', 'Gladness',
        'Halima', 'Irene', 'Josephine', 'Kulwa', 'Lucy', 'Mariam', 'Nasra', 'Ombeni', 'Paulina', 'Rehema',
        'Salma', 'Tatu', 'Ukweli', 'Victoria', 'Winnie', 'Yasmin', 'Zaituni'
    ];

    /**
     * Swahili father names
     */
    private $swahiliFatherNames = [
        'Mzee Amani', 'Baba Baraka', 'Mzee Daudi', 'Baba Erick', 'Mzee Faraji', 'Baba Hamisi', 'Mzee Ibrahim',
        'Baba Juma', 'Mzee Kelvin', 'Baba Lameck', 'Mzee Mwalimu', 'Baba Nuru', 'Mzee Omari', 'Baba Paulo',
        'Mzee Rashidi', 'Baba Salim', 'Mzee Tumaini', 'Baba Upendo', 'Mzee Venance', 'Baba Waziri',
        'Mzee Yusuph', 'Baba Zuberi', 'Mzee Abdallah', 'Baba Bakari', 'Mzee Chande', 'Baba Dotto',
        'Mzee Elias', 'Baba Fidelis', 'Mzee Goodluck', 'Baba Hassan', 'Mzee Idris', 'Baba Japhet',
        'Mzee Kalunde', 'Baba Lipemba', 'Mzee Mhina', 'Baba Ndunguru', 'Mzee Othman', 'Baba Pili',
        'Mzee Ramadhani', 'Baba Shaban', 'Mzee Tabu', 'Baba Ukweli', 'Mzee Vicent', 'Baba Wambura',
        'Mzee Yahya', 'Baba Zakayo'
    ];

    /**
     * Swahili mother names
     */
    private $swahiliMotherNames = [
        'Mama Asha', 'Bi Bahati', 'Mama Dorcas', 'Bi Esther', 'Mama Fatuma', 'Bi Grace', 'Mama Hawa',
        'Bi Imelda', 'Mama Jokate', 'Bi Khadija', 'Mama Latifa', 'Bi Mwajuma', 'Mama Neema', 'Bi Olivia',
        'Mama Pendo', 'Bi Rahma', 'Mama Sara', 'Bi Tumaini', 'Mama Upendo', 'Bi Vaileth', 'Mama Wema',
        'Bi Yusta', 'Mama Zuhura', 'Bi Amina', 'Mama Beatrice', 'Bi Christina', 'Mama Deborah',
        'Bi Elizabeth', 'Mama Farida', 'Bi Gladness', 'Mama Halima', 'Bi Irene', 'Mama Josephine',
        'Bi Kulwa', 'Mama Lucy', 'Bi Mariam', 'Mama Nasra', 'Bi Ombeni', 'Mama Paulina', 'Bi Rehema',
        'Mama Salma', 'Bi Tatu', 'Mama Ukweli', 'Bi Victoria', 'Mama Winnie', 'Bi Yasmin', 'Mama Zaituni'
    ];

    /**
     * Tanzanian regions
     */
    private $tanzanianRegions = [
        'Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi', 'Kigoma',
        'Kilimanjaro', 'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Morogoro', 'Mtwara', 'Mwanza',
        'Njombe', 'Pemba Kaskazini', 'Pemba Kusini', 'Pwani', 'Rukwa', 'Ruvuma', 'Shinyanga',
        'Simiyu', 'Singida', 'Tabora', 'Tanga', 'Unguja Kaskazini', 'Unguja Kusini'
    ];

    /**
     * Tanzanian wards/areas
     */
    private $tanzanianWards = [
        'Kariakoo', 'Kinondoni', 'Ilala', 'Temeke', 'Ubungo', 'Kigamboni', 'Kivukoni', 'Upanga',
        'Magomeni', 'Sinza', 'Msimbazi', 'Buguruni', 'Tabata', 'Kijitonyama', 'Mwenge', 'Mikocheni',
        'Msasani', 'Oyster Bay', 'Masaki', 'Ada Estate', 'Kimara', 'Goba', 'Mbezi', 'Tegeta',
        'Kunduchi', 'Bunju', 'Kawe', 'Mbweni', 'Kibaha', 'Chalinze', 'Bagamoyo', 'Kisarawe',
        'Mkuranga', 'Rufiji', 'Mafia', 'Kilifi', 'Lushoto', 'Korogwe', 'Handeni', 'Pangani',
        'Muheza', 'Tanga', 'Mkinga', 'Newala', 'Tandahimba', 'Mtwara', 'Lindi', 'Kilwa',
        'Nachingwea', 'Ruangwa', 'Liwale'
    ];

    /**
     * Tanzanian streets/areas
     */
    private $tanzanianStreets = [
        'Barabara ya Uhuru', 'Mtaa wa Amani', 'Barabara ya Jamhuri', 'Mtaa wa Upendo', 'Barabara ya Mwalimu Nyerere',
        'Mtaa wa Tumaini', 'Barabara ya Azikiwe', 'Mtaa wa Furaha', 'Barabara ya Mandela', 'Mtaa wa Haki',
        'Barabara ya Karume', 'Mtaa wa Uzalendo', 'Barabara ya Sokoine', 'Mtaa wa Maendeleo', 'Barabara ya Kawawa',
        'Mtaa wa Vijana', 'Barabara ya Msimbazi', 'Mtaa wa Wazazi', 'Barabara ya Morogoro', 'Mtaa wa Elimu',
        'Barabara ya Nelson Mandela', 'Mtaa wa Uhuru', 'Barabara ya Bibi Titi Mohamed', 'Mtaa wa Umoja',
        'Barabara ya Ali Hassan Mwinyi', 'Mtaa wa Kijiji', 'Barabara ya Samora Machel', 'Mtaa wa Mwenge',
        'Barabara ya Kilimanjaro', 'Mtaa wa Mwananyamala', 'Barabara ya Bagamoyo', 'Mtaa wa Makongo',
        'Barabara ya Kimara', 'Mtaa wa Mbezi', 'Barabara ya University', 'Mtaa wa Sinza'
    ];

    /**
     * Tanzanian health facilities
     */
    private $tanzanianHealthFacilities = [
        'Muhimbili National Hospital', 'Bugando Medical Centre', 'Kilimanjaro Christian Medical Centre',
        'Mbeya Zonal Referral Hospital', 'Dodoma Regional Hospital', 'Arusha Lutheran Medical Centre',
        'Kitete Regional Hospital', 'Ligula Regional Hospital', 'Tumbi Regional Hospital',
        'Sekou Toure Regional Hospital', 'Mwananyamala Regional Hospital', 'Amana Regional Hospital',
        'Temeke Regional Hospital', 'Mnazi Mmoja Hospital', 'Mirembe National Mental Health Hospital',
        'Kibong\'oto National Tuberculosis Hospital', 'Ocean Road Cancer Institute', 'Jakaya Kikwete Cardiac Institute',
        'Dispensary ya Mtaa', 'Kituo cha Afya cha Kanda', 'Zahanati ya Wilaya', 'Hospitali ya Mkoa',
        'Kituo cha Afya cha Kata', 'Dispensary ya Kijiji', 'Kituo cha Afya cha Msingi'
    ];

    /**
     * Tanzanian birth attendant names/titles
     */
    private $tanzanianBirthAttendants = [
        'Mkunga Fatuma', 'Muuguzi Asha', 'Daktari Amina', 'Mkunga Halima', 'Muuguzi Rehema',
        'Daktari Khadija', 'Mkunga Zuhura', 'Muuguzi Pendo', 'Daktari Neema', 'Mkunga Wema',
        'Muuguzi Upendo', 'Daktari Bahati', 'Mkunga Latifa', 'Muuguzi Grace', 'Daktari Esther',
        'Mkunga Mariam', 'Muuguzi Sara', 'Daktari Olivia', 'Mkunga Rahma', 'Muuguzi Beatrice',
        'Daktari Christina', 'Mkunga Deborah', 'Muuguzi Elizabeth', 'Daktari Farida'
    ];

    public function definition()
    {
        // Create children aged 6 years back to yesterday to ensure they have appointments/services for today
        $startDate = now()->subYears(6); // 6 years back
        $endDate = now()->subDay(); // Up to yesterday
        
        // Generate Swahili child name
        $childName = $this->faker->randomElement($this->swahiliChildNames);
        
        // Generate date of birth that will make them eligible for services today
        $dateOfBirth = $this->generateEligibleDateOfBirth($startDate, $endDate);
        
        return [
            'user_id' => \App\Models\User::factory(),
            // childNo will be automatically generated by the Child model based on date_of_birth
            'childName' => $childName,
            'date_of_birth' => $dateOfBirth,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'birthWeight' => $this->faker->randomFloat(2, 2.0, 5.0),
            'birthHeight' => $this->faker->randomFloat(2, 40.0, 60.0),
            'fatherName' => $this->faker->randomElement($this->swahiliFatherNames),
            'motherName' => $this->faker->randomElement($this->swahiliMotherNames),
            'birthFacility' => $this->faker->randomElement($this->tanzanianHealthFacilities),
            'birthAttendant' => $this->faker->randomElement($this->tanzanianBirthAttendants),
            'email' => $this->faker->unique()->safeEmail(),
            'phoneNo' => $this->faker->randomElement(['255674960366', '255766418267', '255742795712', '255753303422', '255765814036', '255745456947']),
            'address' => [
                'street' => $this->faker->randomElement($this->tanzanianStreets),
                'ward' => $this->faker->randomElement($this->tanzanianWards),
                'Region' => $this->faker->randomElement($this->tanzanianRegions),
            ],
            'motherAge' => $this->faker->numberBetween(18, 45),
        ];
    }    /**
     * Generate date of birth that makes child eligible for services today
     */
    private function generateEligibleDateOfBirth($startDate, $endDate)
    {
        // Create children who are exactly at vaccination/vitamin ages TODAY
        // This means they were born exactly X weeks/months ago
        
        $eligibleBirthDates = [];
        
        // Vaccination schedule - children born exactly these weeks ago are due for vaccines TODAY
        $vaccinationWeeks = [6, 10, 14, 39, 78];
        foreach ($vaccinationWeeks as $weeks) {
            $birthDate = now()->subWeeks($weeks);
            if ($birthDate->between($startDate, $endDate)) {
                $eligibleBirthDates[] = $birthDate;
            }
        }
        
        // Vitamin schedule - children born exactly these months ago are due for vitamins TODAY
        $vitaminMonths = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60];
        foreach ($vitaminMonths as $months) {
            $birthDate = now()->subMonths($months);
            if ($birthDate->between($startDate, $endDate)) {
                $eligibleBirthDates[] = $birthDate;
            }
        }
        
        // Add some children who need growth monitoring (any age under 5 years)
        // These will be spread across different ages for variety
        $growthMonitoringAges = [1, 2, 3, 4, 5, 7, 8, 9, 11, 13, 15, 16, 17, 19, 20, 21, 23, 25, 26, 27, 29, 31, 32, 33, 35, 37, 38, 40, 41, 43, 44, 45, 47, 49, 50, 51, 53, 55, 56, 57, 59]; // months
        foreach ($growthMonitoringAges as $months) {
            $birthDate = now()->subMonths($months);
            if ($birthDate->between($startDate, $endDate)) {
                $eligibleBirthDates[] = $birthDate;
            }
        }
        
        // If we have eligible birth dates, pick one randomly
        if (!empty($eligibleBirthDates)) {
            return $this->faker->randomElement($eligibleBirthDates);
        }
        
        // Fallback: create a child at 6 weeks (eligible for first vaccines)
        return now()->subWeeks(6);
    }

    public function configure()
    {
        return $this->afterCreating(function (Child $child) {
            // Vitamin and deworming records are now automatically created 
            // by the Child model's booted method, no need to create them here
            
            // Ensure the child has appointments for today
            $this->createTodaysAppointments($child);
        });
    }    /**
     * Create appointments for today based on child's age and eligibility
     */
    private function createTodaysAppointments(Child $child)
    {
        $today = now()->format('Y-m-d');
        $dob = Carbon::parse($child->date_of_birth);
        $ageInWeeks = $dob->diffInWeeks(now());
        $ageInMonths = $dob->diffInMonths(now());
        
        // Vaccination appointments for today - check for exact or close matches
        $vaccinationSchedule = [
            6 => ['bOPV-1', 'Rota-1', 'DPT-HepB-Hib-1', 'PCV13-1'],
            10 => ['bOPV-2', 'Rota-2', 'DPT-HepB-Hib-2', 'PCV13-2'],
            14 => ['bOPV-3', 'Rota-3', 'DPT-HepB-Hib-3', 'PCV13-3', 'IPV'],
            39 => ['Surua Rubella-1'],
            78 => ['Surua Rubella-2']
        ];
        
        // Check if child is eligible for vaccination today
        foreach ($vaccinationSchedule as $weekAge => $vaccines) {
            $ageDiff = abs($ageInWeeks - $weekAge);
            if ($ageDiff <= 1) { // Allow 1 week tolerance
                foreach ($vaccines as $vaccineName) {
                    \App\Models\Appointment::firstOrCreate([
                        'child_id' => $child->id,
                        'appointment_type' => 'vaccination',
                        'appointment_date' => $today,
                        'appointment_name' => $vaccineName,
                    ]);
                }
                echo "Created vaccination appointments for {$child->childName} (Age: {$ageInWeeks} weeks)\n";
                break; // Only create appointments for one age group
            }
        }
        
        // Vitamin and deworming appointments for today
        $vitaminSchedule = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60]; // months
        foreach ($vitaminSchedule as $visitMonth) {
            $ageDiff = abs($ageInMonths - $visitMonth);
            if ($ageDiff <= 1) { // Allow 1 month tolerance
                $visitNumber = array_search($visitMonth, $vitaminSchedule) + 1;
                \App\Models\Appointment::firstOrCreate([
                    'child_id' => $child->id,
                    'appointment_type' => 'vitamin_deworming',
                    'appointment_date' => $today,
                    'appointment_name' => 'Vitamin A & Deworming - Visit ' . $visitNumber,
                ]);
                echo "Created vitamin appointment for {$child->childName} (Age: {$ageInMonths} months)\n";
                break; // Only create appointments for one age group
            }
        }
        
        // Growth monitoring appointment (every child under 5 years needs monthly growth monitoring)
        if ($ageInMonths <= 60) {
            \App\Models\Appointment::firstOrCreate([
                'child_id' => $child->id,
                'appointment_type' => 'growth_monitoring',
                'appointment_date' => $today,
                'appointment_name' => 'Growth Monitoring',
            ]);
            echo "Created growth monitoring appointment for {$child->childName}\n";
        }
    }/**
     * Create a child specifically eligible for vaccination services today
     */
    public function eligibleForVaccinationToday()
    {
        return $this->state(function (array $attributes) {
            // Pick exactly one vaccination age - child will be exactly this age today
            $vaccinationWeeks = [6, 10, 14, 39, 78];
            $selectedWeek = $this->faker->randomElement($vaccinationWeeks);
            
            return [
                'date_of_birth' => now()->subWeeks($selectedWeek)->toDate(),
            ];
        });
    }

    /**
     * Create a child specifically eligible for vitamin/deworming services today
     */
    public function eligibleForVitaminsToday()
    {
        return $this->state(function (array $attributes) {
            // Pick exactly one vitamin age - child will be exactly this age today
            $vitaminMonths = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60];
            $selectedMonth = $this->faker->randomElement($vitaminMonths);
            
            return [
                'date_of_birth' => now()->subMonths($selectedMonth)->toDate(),
            ];
        });
    }

    /**
     * Create a child eligible for growth monitoring today (any child under 5 years)
     */
    public function eligibleForGrowthMonitoringToday()
    {
        return $this->state(function (array $attributes) {
            // Any child between 1 month to 60 months (5 years) needs growth monitoring
            $randomMonths = $this->faker->numberBetween(1, 60);
            
            return [
                'date_of_birth' => now()->subMonths($randomMonths)->toDate(),
            ];
        });
    }

    /**
     * Create a child at exactly 6 weeks old (eligible for first vaccines today)
     */
    public function sixWeeksOldToday()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_of_birth' => now()->subWeeks(6)->toDate(),
            ];
        });
    }

    /**
     * Create a child at exactly 10 weeks old (eligible for second vaccines today)
     */
    public function tenWeeksOldToday()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_of_birth' => now()->subWeeks(10)->toDate(),
            ];
        });
    }

    /**
     * Create a child at exactly 14 weeks old (eligible for third vaccines today)
     */
    public function fourteenWeeksOldToday()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_of_birth' => now()->subWeeks(14)->toDate(),
            ];
        });
    }

    /**
     * Create a child at exactly 6 months old (eligible for first vitamins today)
     */
    public function sixMonthsOldToday()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_of_birth' => now()->subMonths(6)->toDate(),
            ];
        });
    }
}
