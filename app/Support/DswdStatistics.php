<?php

namespace App\Support;

use App\Models\Infant;
use App\Models\Mother;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DswdStatistics
{
    public const PREGNANCY = ['pregnant' => 'Pregnant', 'not_pregnant' => 'Not pregnant', 'postpartum' => 'Postpartum', 'planning' => 'Planning pregnancy', 'unknown' => 'Not recorded'];

    public function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality_city' => ['nullable', 'string', 'max:255'],
            'pregnancy_status' => ['nullable', Rule::in(array_keys(self::PREGNANCY))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);
    }

    public function mothers(array $filters = []): Builder
    {
        // Never pass a full medical model to the DSWD views or exports.
        $query = Mother::query()->select(['id', 'first_name', 'middle_name', 'last_name', 'barangay', 'municipality_city', 'pregnancy_status', 'created_at'])
            ->where('is_4ps_beneficiary', true);
        foreach (['barangay', 'municipality_city'] as $column) {
            if ($value = ($filters[$column] ?? null)) {
                $value === '__unrecorded__'
                    ? $query->where(fn ($q) => $q->whereNull($column)->orWhere($column, ''))
                    : $query->where($column, $value);
            }
        }
        if ($status = ($filters['pregnancy_status'] ?? null)) {
            $status === 'unknown'
                ? $query->where(fn ($q) => $q->whereNull('pregnancy_status')->orWhereNotIn('pregnancy_status', ['pregnant', 'not_pregnant', 'postpartum', 'planning']))
                : $query->where('pregnancy_status', $status);
        }
        if ($filters['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if ($search = ($filters['q'] ?? null)) {
            $query->where(function ($q) use ($search) {
                $q->where(function ($names) use ($search) {
                    foreach (preg_split('/\s+/', trim($search)) as $term) {
                        $names->where(function ($part) use ($term) {
                            foreach (['first_name', 'middle_name', 'last_name', 'barangay'] as $column) {
                                $part->orWhere($column, 'like', '%'.$term.'%');
                            }
                        });
                    }
                });
                $id = preg_replace('/^INAY-/i', '', $search);
                if (ctype_digit($id)) {
                    $q->orWhere('id', (int) $id);
                }
            });
        }
        return $query;
    }

    public function youngChildren(Builder $query): void
    {
        // 0–24 completed calendar months; exclude future/unknown birth dates.
        $query->whereDate('birth_date', '>', today()->subMonthsNoOverflow(25))
            ->whereDate('birth_date', '<=', today());
    }

    public function withChildren(Builder $query): Builder
    {
        return $query->withCount(['infants as young_children_count' => fn (Builder $q) => $this->youngChildren($q)]);
    }

    public function options(): array
    {
        $options = [];
        foreach (['barangay', 'municipality_city'] as $column) {
            $options[$column] = Mother::where('is_4ps_beneficiary', true)->whereNotNull($column)->where($column, '!=', '')
                ->distinct()->orderBy($column)->pluck($column);
        }
        return $options;
    }

    public function summary(array $filters): array
    {
        $mothers = $this->mothers($filters);
        $children = Infant::query()->whereIn('mother_id', (clone $mothers)->select('id'));
        $this->youngChildren($children);
        $pregnancy = (clone $mothers)->select('pregnancy_status')->selectRaw('COUNT(*) AS total')->groupBy('pregnancy_status')->pluck('total', 'pregnancy_status');
        $distribution = array_fill_keys(array_keys(self::PREGNANCY), 0);
        foreach ($pregnancy as $key => $count) {
            $distribution[array_key_exists($key, $distribution) ? $key : 'unknown'] += $count;
        }
        $ages = [];
        foreach ([[0, 6], [7, 12], [13, 24]] as [$min, $max]) {
            $ages[$min.'–'.$max.' months'] = (clone $children)
                ->whereDate('birth_date', '>', today()->subMonthsNoOverflow($max + 1))
                ->whereDate('birth_date', '<=', today()->subMonthsNoOverflow($min))->count();
        }
        $groups = [];
        foreach (['barangay', 'municipality_city'] as $column) {
            $groups[$column] = (clone $mothers)->select([])->selectRaw("COALESCE(NULLIF($column, ''), 'Not recorded') AS label, COUNT(*) AS total")
                ->groupBy('label')->orderByDesc('total')->orderBy('label')->pluck('total', 'label')->all();
        }
        return [
            'total' => (clone $mothers)->count(),
            'pregnant' => $distribution['pregnant'],
            'mothers_with_children' => (clone $mothers)->whereHas('infants', fn (Builder $q) => $this->youngChildren($q))->count(),
            'children' => $children->count(),
            'pregnancy' => $distribution, 'ages' => $ages, 'groups' => $groups,
        ];
    }
}
