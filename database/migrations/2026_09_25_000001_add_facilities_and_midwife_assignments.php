<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('healthcare_facilities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('barangay')->nullable();
            $table->string('identity_key', 64)->unique();
            $table->timestamps();
        });

        Schema::create('midwife_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_staff_id')->nullable()->unique()->constrained('program_staff')->restrictOnDelete();
            $table->foreignId('healthcare_facility_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by_staff_id')->nullable()->constrained('program_staff')->nullOnDelete();
            $table->string('full_name');
            $table->string('identity_key', 64)->unique();
            $table->string('contact_number', 30)->nullable();
            $table->string('availability_status', 20)->default('available');
            $table->timestamps();
        });

        Schema::table('program_staff', function (Blueprint $table): void {
            $table->foreignId('healthcare_facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_midwife_id')->nullable()->constrained('midwife_profiles')->nullOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('healthcare_facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('midwife_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->json('care_team_snapshot')->nullable();
        });

        // Link existing staff to facilities without changing their profile text or appointments.
        DB::table('program_staff')->orderBy('id')->each(function ($staff): void {
            $name = trim((string) $staff->assigned_facility);
            if ($name === '') {
                return;
            }
            $barangay = trim((string) $staff->assigned_barangay);
            $normalize = fn (string $value): string => mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
            $key = hash('sha256', $normalize($name).'|'.$normalize($barangay));
            DB::table('healthcare_facilities')->insertOrIgnore([
                'identity_key' => $key, 'name' => $name, 'barangay' => $barangay ?: null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('program_staff')->where('id', $staff->id)->update([
                'healthcare_facility_id' => DB::table('healthcare_facilities')->where('identity_key', $key)->value('id'),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('midwife_profile_id');
            $table->dropConstrainedForeignId('healthcare_facility_id');
            $table->dropColumn('care_team_snapshot');
        });
        Schema::table('program_staff', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_midwife_id');
            $table->dropConstrainedForeignId('healthcare_facility_id');
        });
        Schema::dropIfExists('midwife_profiles');
        Schema::dropIfExists('healthcare_facilities');
    }
};
