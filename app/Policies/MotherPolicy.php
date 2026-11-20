<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Mother;
use App\Models\ProgramStaff;
use App\Models\StaffMotherCasefile;

class MotherPolicy
{
    public function view(object $staff, Mother $mother): bool
    {
        return $staff instanceof ProgramStaff && $staff->is_approved && StaffMotherCasefile::where('staff_id', $staff->id)
            ->where('mother_id', $mother->id)->exists();
    }

    public function update(object $staff, Mother $mother): bool
    {
        return $this->view($staff, $mother);
    }

    public function download(object $staff, Mother $mother): bool
    {
        return $this->view($staff, $mother);
    }

    public function export(object $staff): bool
    {
        return $staff instanceof ProgramStaff && $staff->is_approved;
    }

    public function assign(object $actor): bool
    {
        // Clinical job titles are self-declared at registration, not permissions.
        return $actor instanceof AdminUser && in_array($actor->role, ['admin', 'admin_cho'], true);
    }
}
