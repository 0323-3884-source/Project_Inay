<?php

namespace Database\Seeders;

use App\Models\{Mother, ProgramStaff};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCasefileAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo casefile assignment is available only locally or in tests.');
        }

        $emails = ['demo.mother@example.test'];
        for ($index = 1; $index <= 50; $index++) {
            $emails[] = sprintf('demo.mother.%03d@example.test', $index);
        }
        $motherIds = Mother::whereIn('email', $emails)->where('middle_name', 'Demo')->pluck('id');
        $staff = ProgramStaff::where('approval_status', 'approved')->get();
        DB::transaction(function () use ($staff, $motherIds) {
            foreach ($staff as $account) {
                $account->casefileMothers()->syncWithoutDetaching($motherIds);
            }
        });
        $this->command?->info($motherIds->count().' fictional mother casefiles assigned to '.$staff->count().' approved Program Staff accounts. Existing assignments preserved.');
    }
}
