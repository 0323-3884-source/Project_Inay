<?php

namespace App\Support;

use App\Models\F1kdMonitoring;
use App\Models\Infant;
use App\Models\Mother;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class F1kdCompliance
{
    public const STATUSES = ['compliant' => 'Compliant', 'non_compliant' => 'Non-Compliant', 'verification' => 'For Verification / Not Yet Recorded'];
    public const ATTENDANCE = ['attended' => 'Attended', 'did_not_attend' => 'Did Not Attend'];
    // Reuse the existing checklist outcome keys; these are application labels,
    // not a claim to implement official external DSWD numeric remark codes.
    public const REMARKS = [
        'service_unavailable' => 'Required service not available',
        'miscarriage' => 'Miscarriage',
        'delivered' => 'Delivered',
        'death' => 'Maternal / neonatal death',
        'other_verification' => 'Other / needs verification',
    ];

    public static function attendanceStatus(?string $attendance): string
    {
        return match ($attendance) {
            'attended' => 'compliant',
            'did_not_attend' => 'non_compliant',
            default => 'verification',
        };
    }
    public const CLASSES = ['pregnant' => 'Pregnant Woman', 'child' => 'Child 0–24 Months'];
    public const MATERNAL = [
        'immunization' => 'Maternal immunization / tetanus and diphtheria toxoid',
        'micronutrients' => 'Micronutrient supplementation (iron-folic acid, calcium, iodine)',
        'vitals' => 'Blood pressure and weight monitoring',
        'prenatal' => 'Prenatal consultations/visits',
        'delivery' => 'Delivery services by a skilled health professional',
        'service_unavailable' => 'Required service not available',
        'miscarriage' => 'Miscarriage', 'delivered' => 'Delivered',
        'death' => 'Maternal/neonatal death, when applicable',
    ];
    public const CHILD = [
        'newborn_care' => 'Essential intrapartum/newborn care',
        'screening' => 'Newborn screening', 'eye_prophylaxis' => 'Eye prophylaxis',
        'vitamin_k' => 'Vitamin K supplementation', 'immunization' => 'Routine immunization',
        'growth' => 'Growth and development monitoring',
        'nutrition_oral' => 'Health and nutrition interventions / oral health services',
        'postnatal' => 'Postnatal consultations/visits',
        'service_unavailable' => 'Required service not available',
    ];

    public function conditions(string $classification): array
    {
        return $classification === 'child' ? self::CHILD : self::MATERNAL;
    }

    public function defaults(string $classification): array
    {
        // Outcomes require explicit review; they are never inferred from medical data.
        return array_fill_keys(array_keys($this->conditions($classification)), 'verification');
    }

    public function status(array $checklist): string
    {
        if (in_array('unavailable', $checklist, true)) return 'unavailable';
        if (in_array('verification', $checklist, true)) return 'verification';
        return in_array('compliant', $checklist, true) ? 'compliant' : 'not_applicable';
    }

    public function filters(Request $request): array
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality_city' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', Rule::in(array_keys(self::CLASSES))],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $filters['month'] = $filters['month'] ?? now()->format('Y-m');
        return $filters;
    }

    public function rows(array $filters = []): Collection
    {
        $month = CarbonImmutable::parse(($filters['month'] ?? now()->format('Y-m')).'-01');
        $records = F1kdMonitoring::whereDate('reporting_month', $month)->get()->keyBy('subject_key');
        $mothers = Mother::where('is_4ps_beneficiary', true)
            ->when(isset($filters['mother_id']), fn ($query) => $query->where('id', $filters['mother_id']))
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'barangay', 'municipality_city', 'pregnancy_status'])->keyBy('id');
        $children = Infant::whereIn('mother_id', $mothers->keys())
            ->get(['id', 'mother_id', 'full_name', 'sex', 'birth_date'])->keyBy('id');
        $rows = collect();
        $current = $month->format('Y-m') === now()->format('Y-m');
        $asOf = $current ? CarbonImmutable::today() : $month->endOfMonth();
        $add = function ($mother, $child, $record = null) use (&$rows, $month) {
            $key = $child ? 'child-'.$child->id : 'mother-'.$mother->id;
            $classification = $child ? 'child' : 'pregnant';
            $checklist = $record?->checklist ?? $this->defaults($classification);
            $rows->put($key, (object) [
                'key' => $key, 'mother_id' => $mother->id, 'infant_id' => $child?->id,
                'household' => 'INAY-'.str_pad($mother->id, 5, '0', STR_PAD_LEFT),
                'beneficiary_id' => $child ? 'CHILD-'.$child->id : 'INAY-'.str_pad($mother->id, 5, '0', STR_PAD_LEFT),
                'name' => $child?->full_name ?? $mother->full_name, 'sex' => $child?->sex ?? 'Female',
                'mother_name' => $mother->full_name,
                'barangay' => $record ? ($record->barangay ?: 'Not recorded') : ($mother->barangay ?: 'Not recorded'),
                'municipality_city' => $record ? ($record->municipality_city ?: 'Not recorded') : ($mother->municipality_city ?: 'Not recorded'),
                'classification' => $classification, 'month' => $month->format('Y-m'),
                'checklist' => $checklist, 'status' => self::attendanceStatus($record?->attendance_status),
                'attendance_status' => $record?->attendance_status, 'remark_code' => $record?->remark_code,
                'verification' => array_intersect_key($this->conditions($classification), array_filter($checklist, fn ($s) => $s === 'verification')),
                'updated_at' => $record?->updated_at,
            ]);
        };
        foreach ($records as $record) {
            $mother = $mothers->get($record->mother_id);
            $child = $record->infant_id ? $children->get($record->infant_id) : null;
            if ($mother && (! $record->infant_id || $child)) $add($mother, $child, $record);
        }
        if ($current) {
            foreach ($mothers as $mother) {
                if ($mother->pregnancy_status === 'pregnant' && ! $rows->has('mother-'.$mother->id)) $add($mother, null);
            }
            foreach ($children as $child) {
                if ($child->birth_date && $child->birth_date->lte($asOf) && $child->birth_date->gt($asOf->subMonthsNoOverflow(25)) && ! $rows->has('child-'.$child->id)) $add($mothers->get($child->mother_id), $child);
            }
        }
        return $rows->filter(function ($row) use ($filters) {
            foreach (['barangay', 'municipality_city', 'classification', 'status'] as $field) {
                if (! empty($filters[$field]) && $row->$field !== $filters[$field]) return false;
            }
            $q = trim($filters['q'] ?? '');
            return $q === '' || str_contains(mb_strtolower($row->name.' '.$row->mother_name.' '.$row->household.' '.$row->beneficiary_id), mb_strtolower($q));
        })->sortBy('name')->values();
    }

    public function summary(Collection $rows): array
    {
        return [
            'total' => $rows->count(), 'pregnant' => $rows->where('classification', 'pregnant')->count(),
            'children' => $rows->where('classification', 'child')->count(),
            'compliant' => $rows->where('status', 'compliant')->count(),
            'verification' => $rows->where('status', 'verification')->count(),
            'non_compliant' => $rows->where('status', 'non_compliant')->count(),
        ];
    }

    public function beneficiary(string $subject, string $month): ?object
    {
        $row = $this->rows(['month' => $month])->firstWhere('key', $subject);
        if ($row) return $row;

        // Keep aggregate history limited to saved records. A detail page can
        // still show an empty period for an eligible beneficiary or a subject
        // with a saved history (including children who have since aged out).
        $row = $this->rows()->firstWhere('key', $subject);
        if (! $row) {
            $previous = F1kdMonitoring::where('subject_key', $subject)->latest('reporting_month')->first();
            if ($previous) $row = $this->rows(['month' => $previous->reporting_month->format('Y-m')])->firstWhere('key', $subject);
        }
        if (! $row) return null;

        $row->month = $month;
        $row->attendance_status = $row->remark_code = $row->updated_at = null;
        $row->status = 'verification';
        return $row;
    }
}
