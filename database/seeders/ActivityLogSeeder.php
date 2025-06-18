<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users for the logs (create sample users if none exist)
        $users = User::all();

        if ($users->isEmpty()) {
            // Create sample users
            $users = collect([
                User::create([
                    'name' => 'Dr. John Smith',
                    'username' => 'dr.smith',
                    'password' => bcrypt('password123'),
                ]),
                User::create([
                    'name' => 'Admin User',
                    'username' => 'admin',
                    'password' => bcrypt('password123'),
                ]),
                User::create([
                    'name' => 'Nurse Jane',
                    'username' => 'nurse.jane',
                    'password' => bcrypt('password123'),
                ]),
            ]);
        }

        $actions = [
            ['action' => 'Login', 'type' => 'login', 'status' => 'success', 'details' => 'Successful login to dashboard'],
            ['action' => 'Logout', 'type' => 'logout', 'status' => 'success', 'details' => 'User logged out successfully'],
            ['action' => 'Create Child Record', 'type' => 'create', 'status' => 'success', 'details' => 'Added new child record'],
            ['action' => 'Update Growth Record', 'type' => 'update', 'status' => 'success', 'details' => 'Updated growth measurements'],
            ['action' => 'Failed Login', 'type' => 'login', 'status' => 'error', 'details' => 'Invalid credentials attempt'],
            ['action' => 'Password Reset', 'type' => 'update', 'status' => 'warning', 'details' => 'Password reset requested'],
            ['action' => 'Delete Record', 'type' => 'delete', 'status' => 'warning', 'details' => 'Record deleted by user'],
            ['action' => 'Export Data', 'type' => 'info', 'status' => 'info', 'details' => 'Data exported to CSV'],
            ['action' => 'System Backup', 'type' => 'info', 'status' => 'success', 'details' => 'System backup completed'],
            ['action' => 'Error Occurred', 'type' => 'error', 'status' => 'error', 'details' => 'Database connection error'],
        ];

        $ipAddresses = [
            '192.168.1.100',
            '192.168.1.101',
            '192.168.1.102',
            '10.0.0.1',
            '172.16.0.1',
            '203.0.113.1',
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ];

        // Generate logs for the past 30 days
        for ($day = 30; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);

            // Generate 3-15 logs per day
            $logsPerDay = rand(3, 15);

            for ($i = 0; $i < $logsPerDay; $i++) {
                $user = $users->random();
                $action = $actions[array_rand($actions)];

                // Add some time variance within the day
                $logTime = $date->copy()->addMinutes(rand(0, 1439)); // 0-1439 minutes in a day

                ActivityLog::create([
                    'user_id' => $user->id,
                    'role' => $user->getRoleNames()->first() ?? 'user',
                    'user_name' => $user->name,
                    'action' => $action['action'],
                    'details' => $action['details'],
                    'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                    'user_agent' => $userAgents[array_rand($userAgents)],
                    'status' => $action['status'],
                    'type' => $action['type'],
                    'created_at' => $logTime,
                    'updated_at' => $logTime,
                ]);
            }
        }

        $this->command->info('Activity logs seeded successfully!');
    }
}
