<?php

namespace App\Http\Controllers;

use App\Models\{Mother, Infant, InfantGrowthRecord, ProgramStaff, StaffMotherCasefile};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfflineRecordController extends Controller
{
    private function staff(Request $request): ProgramStaff
    {
        abort_unless($request->session()->get('auth_role') === 'staff', 401);
        return ProgramStaff::findOrFail($request->session()->get('auth_id'));
    }

    public function index(Request $request)
    {
        $staff = $this->staff($request);
        return view('staff.offline-records', ['owner' => hash_hmac('sha256', 'staff:'.$staff->id, config('app.key'))]);
    }

    public function context(Request $request)
    {
        $staff = $this->staff($request);
        $ids = StaffMotherCasefile::where('staff_id', $staff->id)->pluck('mother_id');
        return response()->json([
            'owner' => hash_hmac('sha256', 'staff:'.$staff->id, config('app.key')),
            'csrf' => csrf_token(),
            'mothers' => Mother::whereIn('id', $ids)->get()->map(fn ($m) => ['id' => $m->id, 'name' => $m->full_name]),
            'children' => Infant::whereIn('mother_id', $ids)->get(['id', 'full_name'])->map(fn ($i) => ['id' => $i->id, 'name' => $i->full_name]),
            'testTypes' => \App\Support\MaternalVitalScreening::bloodSugarTestTypes(),
        ])->header('Cache-Control', 'no-store');
    }

    public function sync(Request $request)
    {
        $staff = $this->staff($request);
        $input = $request->validate([
            'operation_id' => 'required|uuid', 'kind' => 'required|in:maternal,growth',
            'patient_id' => 'required|integer', 'values' => 'required|array',
        ]);
        // Canonical ordering makes a retried operation stable across JSON encoders.
        $values = $input['values'];
        ksort($values);
        $hash = hash('sha256', json_encode([$input['kind'], $input['patient_id'], $values]));

        return DB::transaction(function () use ($request, $staff, $input, $values, $hash) {
            // Serialize retries from different tabs/devices, including the receipt check.
            ProgramStaff::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $receipt = DB::table('offline_sync_receipts')->where('staff_id', $staff->id)->where('operation_id', $input['operation_id'])->first();
            if ($receipt) {
                abort_unless(hash_equals($receipt->payload_hash, $hash), 409, 'This operation was already synchronized with different values.');
                return response()->json(['message' => 'Already synchronized.']);
            }
            $patient = $input['kind'] === 'maternal'
                ? Mother::whereKey($input['patient_id'])->lockForUpdate()->firstOrFail()
                : Infant::whereKey($input['patient_id'])->lockForUpdate()->firstOrFail();
            $motherId = $input['kind'] === 'maternal' ? $patient->id : $patient->mother_id;
            abort_unless(StaffMotherCasefile::where('staff_id', $staff->id)->where('mother_id', $motherId)->exists(), 403);
            // Append only: never overwrite a newer clinical measurement during synchronization.
            $dateField = $input['kind'] === 'maternal' ? 'recorded_at' : 'measured_at';
            $request->replace($values);
            $request->validate([$dateField => 'required|date|before_or_equal:today']);
            $records = $input['kind'] === 'maternal' ? $patient->maternalMonitoringRecords() : $patient->growthRecords();
            if ($records->whereDate($dateField, $values[$dateField])->exists()) {
                return response()->json(['message' => 'A measurement already exists for this patient and date. Review the existing record online; this offline entry has not been uploaded.'], 409);
            }
            if ($input['kind'] === 'maternal') {
                $response = app(AuthController::class)->storeStaffMaternalVitals($request, $patient);
                if ($response->getStatusCode() !== 201) return $response;
            } else {
                $validated = $request->validate([
                    'measured_at' => 'required|date|before_or_equal:today', 'age_months' => 'required|integer|between:0,60',
                    'weight' => 'required|numeric|between:0.5,50', 'height' => 'required|numeric|between:20,130',
                    'head_circumference' => 'nullable|numeric|between:20,70', 'temperature' => 'nullable|numeric|between:34,43',
                    'remarks' => 'nullable|string|max:2000',
                ]);
                InfantGrowthRecord::create([...$validated, 'infant_id' => $patient->id, 'recorded_by_staff_id' => $staff->id]);
            }
            DB::table('offline_sync_receipts')->insert(['staff_id' => $staff->id, 'operation_id' => $input['operation_id'], 'payload_hash' => $hash, 'created_at' => now()]);
            return response()->json(['message' => 'Synchronized successfully.'], 201);
        });
    }
}
