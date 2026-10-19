<?php

namespace Tests\Feature;

use App\Models\{ProgramStaff, Mother, Infant, StaffMotherCasefile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function setupPatient(): array
    {
        $staff = ProgramStaff::create(['first_name' => 'Test', 'last_name' => 'Staff', 'email' => 'offline@example.test', 'password' => 'test', 'staff_id' => 'OFFLINE-1', 'position' => 'Program Staff', 'contact_number' => '09123456789']);
        $mother = Mother::create(['first_name' => 'Test', 'last_name' => 'Mother', 'email' => 'offline-mother@example.test', 'password' => 'test', 'barangay' => 'Test', 'contact_number' => '09123456780', 'age' => 25]);
        StaffMotherCasefile::create(['staff_id' => $staff->id, 'mother_id' => $mother->id]);
        $infant = Infant::create(['mother_id' => $mother->id, 'full_name' => 'Test Child', 'sex' => 'Female', 'birth_date' => now()->subMonths(6)]);
        $this->withSession(['auth_role' => 'staff', 'auth_id' => $staff->id]);
        return [$staff, $mother, $infant];
    }

    private function entry(Infant $infant): array
    {
        return ['operation_id' => (string) Str::uuid(), 'kind' => 'growth', 'patient_id' => $infant->id, 'values' => ['measured_at' => now()->toDateString(), 'age_months' => 6, 'weight' => 7, 'height' => 65]];
    }

    public function test_guest_cannot_open_workspace_or_sync(): void
    {
        $this->getJson('/staff/offline-records/context')->assertUnauthorized();
        $this->postJson('/staff/offline-records/sync', [])->assertUnauthorized();
    }

    public function test_context_and_workspace_are_available_to_staff(): void
    {
        $this->setupPatient();
        $this->get('/staff/offline-records')->assertOk()->assertSee('Offline records');
        $this->getJson('/staff/offline-records/context')->assertOk()->assertJsonCount(1, 'children')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_retries_are_idempotent_and_date_conflicts_are_preserved(): void
    {
        [, , $infant] = $this->setupPatient();
        $entry = $this->entry($infant);
        $this->postJson('/staff/offline-records/sync', $entry)->assertCreated();
        $this->postJson('/staff/offline-records/sync', $entry)->assertOk();
        $this->assertDatabaseCount('infant_growth_records', 1);
        $this->postJson('/staff/offline-records/sync', $this->entry($infant))->assertConflict();
        $entry['values']['weight'] = 8;
        $this->postJson('/staff/offline-records/sync', $entry)->assertConflict();
        $this->assertDatabaseCount('infant_growth_records', 1);
    }

    public function test_revoked_assignment_cannot_sync_and_invalid_values_do_not_create_receipts(): void
    {
        [, , $infant] = $this->setupPatient();
        $entry = $this->entry($infant);
        $entry['values']['weight'] = -1;
        $this->postJson('/staff/offline-records/sync', $entry)->assertUnprocessable();
        $this->assertDatabaseCount('offline_sync_receipts', 0);
        StaffMotherCasefile::query()->delete();
        $this->postJson('/staff/offline-records/sync', $this->entry($infant))->assertForbidden();
    }

    public function test_maternal_measurements_reuse_validation_and_keep_audit_history(): void
    {
        [, $mother] = $this->setupPatient();
        $entry = ['operation_id' => (string) Str::uuid(), 'kind' => 'maternal', 'patient_id' => $mother->id, 'values' => [
            'recorded_at' => now()->toDateString(), 'pregnancy_week' => 20, 'weight' => 60,
            'bp_systolic' => 118, 'bp_diastolic' => 78, 'blood_sugar_test_type' => 'fasting_plasma_glucose',
            'blood_sugar' => 88, 'temperature' => 36.8, 'heart_rate' => 80,
        ]];
        $this->postJson('/staff/offline-records/sync', $entry)->assertCreated();
        $this->postJson('/staff/offline-records/sync', $entry)->assertOk();
        $this->assertDatabaseCount('maternal_monitoring_records', 1);
        $entry['operation_id'] = (string) Str::uuid();
        $this->postJson('/staff/offline-records/sync', $entry)->assertConflict();
    }
}
