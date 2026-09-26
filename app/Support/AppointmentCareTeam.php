<?php

namespace App\Support;

use App\Models\HealthcareFacility;
use App\Models\MidwifeProfile;
use App\Models\ProgramStaff;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AppointmentCareTeam
{
    public function options(): Collection
    {
        $registeredStaff = ProgramStaff::with('facility')->where('approval_status', 'approved')->get()
            ->filter(fn (ProgramStaff $staff): bool => $staff->is_midwife);

        $registered = $registeredStaff
            ->map(fn (ProgramStaff $staff): array => [
                'value' => 'staff:'.$staff->id,
                'name' => $staff->full_name,
                'facility' => $staff->facility?->name ?? $staff->assigned_facility,
                'barangay' => $staff->facility?->barangay ?? $staff->assigned_barangay,
                'contact' => $staff->contact_number,
                'status' => ($staff->accepting_appointments ?? true) ? 'Available' : 'Unavailable',
                'source' => 'Registered midwife',
            ]);

        $registeredNormalizedNames = $registeredStaff->map(fn (ProgramStaff $s): string => HealthcareFacility::normalize($s->full_name))->all();

        $manual = MidwifeProfile::with('facility')->whereNull('program_staff_id')->get()
            ->reject(fn (MidwifeProfile $midwife): bool => in_array(HealthcareFacility::normalize($midwife->full_name), $registeredNormalizedNames, true))
            ->map(fn (MidwifeProfile $midwife): array => [
                'value' => 'profile:'.$midwife->id,
                'name' => $midwife->full_name,
                'facility' => $midwife->facility?->name,
                'barangay' => $midwife->facility?->barangay,
                'contact' => $midwife->contact_number,
                'status' => $midwife->is_available ? 'Available' : 'Unavailable',
                'source' => 'Personnel record',
            ]);

        return $registered->concat($manual)->sortBy('name')->values();
    }

    public function assign(ProgramStaff $staff, array $data): void
    {
        // Older clients can still save a scheduling profile without touching its assignment.
        if (! array_key_exists('midwife_selection', $data)) {
            return;
        }

        $selection = $data['midwife_selection'];
        if (! $selection) {
            $staff->assigned_midwife_id = null;
            return;
        }

        $facility = $staff->facility()->first() ?? HealthcareFacility::forDetails($staff->assigned_facility, $staff->assigned_barangay);
        if (! $facility || ! $facility->barangay) {
            $this->invalid('Set your barangay and healthcare facility before assigning a midwife.');
        }

        if ($selection === 'new') {
            $name = trim(preg_replace('/\s+/u', ' ', $data['midwife_full_name']));
            $normalized = HealthcareFacility::normalize($name);
            // Check real staff identities first, including unapproved accounts, to avoid duplicate personnel.
            $matches = ProgramStaff::all()->filter(fn (ProgramStaff $person): bool =>
                HealthcareFacility::normalize($person->full_name) === $normalized);
            if ($matches->count() > 1) {
                $this->invalid('Multiple registered people have this name. Select the correct existing midwife.');
            }
            if ($registered = $matches->first()) {
                $midwife = $this->registeredProfile($registered);
            } else {
                $midwife = MidwifeProfile::firstOrCreate([
                    'identity_key' => hash('sha256', 'manual:'.$normalized),
                ], [
                    'full_name' => $name,
                    'healthcare_facility_id' => HealthcareFacility::forDetails(
                        $data['midwife_facility'], $data['midwife_barangay']
                    )?->id,
                    'created_by_staff_id' => $staff->id,
                    'contact_number' => $data['midwife_contact_number'] ?? null,
                    'availability_status' => $data['midwife_availability_status'],
                ]);
            }
        } elseif (str_starts_with($selection, 'staff:')) {
            $registered = ProgramStaff::find((int) substr($selection, 6));
            if (! $registered) {
                $this->invalid('The selected registered midwife no longer exists.');
            }
            $midwife = $this->registeredProfile($registered);
        } else {
            $midwife = MidwifeProfile::with(['programStaff.facility', 'facility'])
                ->find((int) substr($selection, 8));
            if (! $midwife || ($midwife->programStaff && (! $midwife->programStaff->is_midwife || ! $midwife->programStaff->is_approved))) {
                $this->invalid('Select an approved midwife or an existing personnel record.');
            }
        }

        if ($midwife->programStaff && ! $midwife->healthcare_facility_id && $midwife->programStaff->assigned_facility) {
            $midwifeFacility = HealthcareFacility::forDetails(
                $midwife->programStaff->assigned_facility,
                $midwife->programStaff->assigned_barangay
            );
            if ($midwifeFacility) {
                $midwife->update(['healthcare_facility_id' => $midwifeFacility->id]);
            }
        }

        if ((int) $midwife->currentFacility()?->id !== (int) $facility->id) {
            $this->invalid('The midwife must be assigned to the same barangay and healthcare facility. Their existing profile has not been changed.');
        }

        // A worker may maintain availability only for a manual record they created.
        if ($selection !== 'new' && ! $midwife->program_staff_id
            && (int) $midwife->created_by_staff_id === (int) $staff->id
            && isset($data['existing_midwife_availability_status'])) {
            $midwife->update(['availability_status' => $data['existing_midwife_availability_status']]);
        }
        $staff->assigned_midwife_id = $midwife->id;
    }

    public function forBooking(ProgramStaff $staff): array
    {
        $staff->loadMissing(['facility', 'assignedMidwife.programStaff.facility', 'assignedMidwife.facility']);
        $midwife = $staff->assignedMidwife;
        if ($midwife && (! $midwife->is_available || ! $staff->healthcare_facility_id
            || (int) $midwife->currentFacility()?->id !== (int) $staff->healthcare_facility_id)) {
            $midwife = null;
        }

        // Booking a midwife directly is an explicit assignment to that professional.
        if (! $midwife && $staff->is_midwife && $staff->is_approved) {
            $midwife = $this->registeredProfile($staff);
        }

        $facilityName = $staff->facility?->name ?? $staff->assigned_facility;
        $barangayName = $staff->facility?->barangay ?? $staff->assigned_barangay;

        $midwifeFacility = $midwife?->currentFacility()?->name
            ?? $midwife?->programStaff?->assigned_facility
            ?? $midwife?->facility?->name
            ?? ($midwife ? $facilityName : null);

        return [
            'healthcare_facility_id' => $staff->healthcare_facility_id,
            'midwife_profile_id' => $midwife?->id,
            'care_team_snapshot' => [
                'facility' => $facilityName,
                'barangay' => $barangayName,
                'worker_name' => $staff->full_name,
                'worker_role' => $staff->role_label,
                'midwife' => $midwife ? [
                    'name' => $midwife->display_name,
                    'role' => $midwife->programStaff?->role_label ?: 'Midwife',
                    'facility' => $midwifeFacility,
                    'contact_number' => $midwife->programStaff?->contact_number ?? $midwife->contact_number,
                ] : null,
            ],
        ];
    }

    private function registeredProfile(ProgramStaff $staff): MidwifeProfile
    {
        if (! $staff->is_midwife || ! $staff->is_approved) {
            $this->invalid('This person already has a staff account, but is not an approved midwife. Select an approved midwife.');
        }

        if (! $staff->healthcare_facility_id && $staff->assigned_facility) {
            $facility = HealthcareFacility::forDetails($staff->assigned_facility, $staff->assigned_barangay);
            if ($facility) {
                $staff->healthcare_facility_id = $facility->id;
                $staff->saveQuietly();
            }
        }

        $existing = MidwifeProfile::where('program_staff_id', $staff->id)->first();
        if ($existing) {
            if ($staff->healthcare_facility_id && (! $existing->healthcare_facility_id || (int) $existing->healthcare_facility_id !== (int) $staff->healthcare_facility_id)) {
                $existing->update(['healthcare_facility_id' => $staff->healthcare_facility_id]);
            }
            return $existing;
        }

        // Reuse a manual record when its owner later registers as a midwife.
        $manual = MidwifeProfile::where('identity_key', hash('sha256', 'manual:'.HealthcareFacility::normalize($staff->full_name)))
            ->whereNull('program_staff_id')->first();
        if ($manual) {
            if ($staff->healthcare_facility_id && $manual->healthcare_facility_id && (int) $manual->healthcare_facility_id !== (int) $staff->healthcare_facility_id) {
                $this->invalid('A personnel record with this name exists at another facility. Resolve the identity before assigning this midwife.');
            }
            $manual->update([
                'program_staff_id' => $staff->id,
                'healthcare_facility_id' => $staff->healthcare_facility_id ?: $manual->healthcare_facility_id,
            ]);
            return $manual->refresh();
        }

        return MidwifeProfile::firstOrCreate(['program_staff_id' => $staff->id], [
            'identity_key' => hash('sha256', 'staff:'.$staff->id),
            'full_name' => $staff->full_name,
            'healthcare_facility_id' => $staff->healthcare_facility_id,
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['midwife_selection' => $message]);
    }
}
