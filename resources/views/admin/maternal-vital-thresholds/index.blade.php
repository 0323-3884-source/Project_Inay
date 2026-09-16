@extends('layouts.admin')

@section('title', 'Maternal Vital Thresholds - Project INAY')

@push('styles')
    <style>
        .threshold-grid { display: grid; gap: 18px; }
        .threshold-card { padding: 20px; background: #ffffff; border: 1px solid #dbe5f1; border-radius: 8px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); }
        .threshold-card h2 { margin: 0; font-size: 19px; font-weight: 900; text-transform: capitalize; }
        .threshold-card > p { margin: 7px 0 16px; color: #52627d; font-size: 13px; }
        .threshold-table-wrap { overflow-x: auto; }
        .threshold-table { width: 100%; min-width: 980px; border-collapse: collapse; }
        .threshold-table th { padding: 10px; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 11px; font-weight: 900; text-align: left; text-transform: uppercase; }
        .threshold-table td { padding: 10px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        .threshold-table input, .threshold-table textarea { width: 100%; color: #071127; background: #ffffff; border: 1px solid #cbd8ea; border-radius: 8px; font: inherit; }
        .threshold-table input { min-height: 40px; padding: 0 10px; }
        .threshold-table textarea { min-height: 68px; padding: 10px; resize: vertical; }
        .threshold-table .is-narrow { width: 98px; }
        .threshold-key { display: block; color: #071127; font-size: 12px; font-weight: 900; }
        .threshold-meta { display: block; margin-top: 4px; color: #64748b; font-size: 11px; font-weight: 800; }
        .threshold-active { display: inline-flex; align-items: center; gap: 8px; color: #334155; font-size: 12px; font-weight: 900; }
        .threshold-active input { width: 18px; min-height: 18px; }
        .threshold-actions { display: flex; justify-content: flex-end; margin-top: 18px; }
        .threshold-save { min-height: 44px; padding: 0 16px; color: #ffffff; background: #00856a; border: 1px solid #00856a; border-radius: 8px; font-weight: 900; cursor: pointer; }
        .threshold-references { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .threshold-reference { display: grid; gap: 8px; padding: 14px; color: #071127; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; text-decoration: none; }
        .threshold-reference:hover { border-color: #9de8c7; text-decoration: none; }
        .threshold-reference strong { font-size: 14px; font-weight: 900; }
        .threshold-reference span { overflow-wrap: anywhere; color: #52627d; font-size: 12px; font-weight: 750; }
        @media (max-width: 760px) { .threshold-references { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <header class="admin-topbar">
        <div>
            <p class="admin-kicker">Admin / Clinical Settings</p>
            <h1 class="admin-page-title">Maternal Vital Screening Thresholds</h1>
            <p class="admin-page-copy">Configurable screening-support thresholds for partner OB-GYN, midwife, or facility review.</p>
        </div>
        <span class="admin-user-chip">{{ $adminUsername }}</span>
    </header>

    @if(session('status'))
        <div class="admin-alert is-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="admin-alert is-error">Please review the highlighted threshold settings.</div>
    @endif

    <form method="POST" action="{{ route('admin.maternal-vital-thresholds.update') }}" class="threshold-grid">
        @csrf
        @method('PATCH')

        @foreach($thresholds as $measurement => $rows)
            <section class="threshold-card">
                <h2>{{ str_replace('_', ' ', $measurement) }}</h2>
                <p>Every active threshold displays its guideline name, version, and reference link beside screening explanations.</p>

                <div class="threshold-table-wrap">
                    <table class="threshold-table">
                        <thead>
                            <tr>
                                <th>Threshold</th>
                                <th class="is-narrow">Value</th>
                                <th>Guideline Name</th>
                                <th>Version</th>
                                <th>Source URL</th>
                                <th>Notes</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $threshold)
                                <tr>
                                    <td>
                                        <span class="threshold-key">{{ $threshold->label }}</span>
                                        <span class="threshold-meta">{{ $threshold->key }} @if($threshold->test_type) / {{ str_replace('_', ' ', $threshold->test_type) }} @endif / {{ $threshold->unit ?: 'unitless' }}</span>
                                    </td>
                                    <td>
                                        <input name="thresholds[{{ $threshold->id }}][value]" value="{{ old('thresholds.'.$threshold->id.'.value', $threshold->value === null ? '' : rtrim(rtrim(number_format((float) $threshold->value, 2), '0'), '.')) }}" inputmode="decimal">
                                    </td>
                                    <td>
                                        <input name="thresholds[{{ $threshold->id }}][guideline_name]" value="{{ old('thresholds.'.$threshold->id.'.guideline_name', $threshold->guideline_name) }}">
                                    </td>
                                    <td>
                                        <input name="thresholds[{{ $threshold->id }}][guideline_version]" value="{{ old('thresholds.'.$threshold->id.'.guideline_version', $threshold->guideline_version) }}">
                                    </td>
                                    <td>
                                        <input name="thresholds[{{ $threshold->id }}][source_url]" value="{{ old('thresholds.'.$threshold->id.'.source_url', $threshold->source_url) }}" inputmode="url">
                                    </td>
                                    <td>
                                        <textarea name="thresholds[{{ $threshold->id }}][notes]">{{ old('thresholds.'.$threshold->id.'.notes', $threshold->notes) }}</textarea>
                                    </td>
                                    <td>
                                        <label class="threshold-active">
                                            <input type="checkbox" name="thresholds[{{ $threshold->id }}][is_active]" value="1" @checked(old('thresholds.'.$threshold->id.'.is_active', $threshold->is_active))>
                                            Active
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        <section class="threshold-card">
            <h2>Clinical Guide References</h2>
            <p>These references are shown in Project INAY as screening guidance sources, not as automated diagnostic rules.</p>
            <div class="threshold-references">
                @foreach($references as $reference)
                    <a class="threshold-reference" href="{{ $reference['url'] }}" target="_blank" rel="noopener">
                        <strong>{{ $reference['title'] }}</strong>
                        <span>{{ $reference['url'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="threshold-actions">
            <button class="threshold-save" type="submit">Save Thresholds</button>
        </div>
    </form>
@endsection
