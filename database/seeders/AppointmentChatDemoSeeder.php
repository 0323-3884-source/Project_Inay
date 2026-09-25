<?php

namespace Database\Seeders;

use App\Models\ProgramStaff;
use App\Models\StaffAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppointmentChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Demo appointment data is only available locally.');
        }

        $miguel = ProgramStaff::where('first_name', 'Miguel')
            ->where('middle_name', 'Ponce')->where('last_name', 'Isles')->sole();

        DB::transaction(function (): void {
            $midwife = ProgramStaff::firstOrCreate(['staff_id' => 'DEMO-MIDWIFE-001'], [
                'first_name' => 'Sample',
                'last_name' => 'Midwife (Test Only)',
                'email' => 'sample.midwife@example.test',
                'password' => Hash::make(Str::random(40)),
                'role' => 'Midwife',
                'position' => 'Midwife',
                'contact_number' => 'Not provided',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'assigned_barangay' => 'San Gabriel',
                'assigned_facility' => 'Sample Health Center (Test Only)',
                'accepting_appointments' => true,
            ]);

            foreach (range(1, 5) as $day) {
                StaffAvailability::firstOrCreate([
                    'staff_id' => $midwife->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                ], [
                    'appointment_type' => 'prenatal_checkup',
                    'meeting_type' => 'in_person',
                    'location' => 'Sample Health Center (Test Only)',
                    'is_active' => true,
                ]);
            }
        });

        Cache::put('local-chat-online:program-staff:'.$miguel->id, true, now()->addDay());
        $this->command?->info('Sample midwife ready. Miguel is shown online locally for 24 hours.');
    }
}
