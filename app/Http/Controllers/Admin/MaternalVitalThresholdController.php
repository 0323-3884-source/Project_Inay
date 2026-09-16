<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaternalVitalThreshold;
use App\Support\MaternalVitalScreening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaternalVitalThresholdController extends Controller
{
    public function index(): View
    {
        $thresholds = MaternalVitalThreshold::query()
            ->orderBy('measurement')
            ->orderBy('test_type')
            ->orderBy('threshold_type')
            ->get()
            ->groupBy('measurement');

        return view('admin.maternal-vital-thresholds.index', [
            'adminUsername' => session('admin_username', 'admin'),
            'thresholds' => $thresholds,
            'references' => MaternalVitalScreening::references(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thresholds' => ['required', 'array'],
            'thresholds.*.value' => ['nullable', 'numeric', 'between:0,1000'],
            'thresholds.*.guideline_name' => ['required', 'string', 'max:255'],
            'thresholds.*.guideline_version' => ['required', 'string', 'max:255'],
            'thresholds.*.source_url' => ['nullable', 'url', 'max:1000'],
            'thresholds.*.notes' => ['nullable', 'string', 'max:5000'],
            'thresholds.*.is_active' => ['nullable', 'boolean'],
        ]);

        $thresholds = MaternalVitalThreshold::query()
            ->whereIn('id', array_keys($validated['thresholds']))
            ->get()
            ->keyBy('id');

        foreach ($validated['thresholds'] as $id => $payload) {
            $threshold = $thresholds->get((int) $id);

            if (! $threshold) {
                continue;
            }

            $threshold->update([
                'value' => $payload['value'] ?? null,
                'guideline_name' => $payload['guideline_name'],
                'guideline_version' => $payload['guideline_version'],
                'source_url' => $payload['source_url'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? false),
            ]);
        }

        MaternalVitalScreening::clearCache();

        return redirect()
            ->route('admin.maternal-vital-thresholds.index')
            ->with('status', 'Maternal vital screening thresholds updated.');
    }
}
