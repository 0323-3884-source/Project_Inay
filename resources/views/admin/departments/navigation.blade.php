@php
    $department = $departmentRole === 'admin_dswd' ? 'dswd' : 'cho';
    $departmentLinks = $department === 'dswd' ? [
        ['Dashboard', 'stats', route('admin.dswd.dashboard')],
        ['F1KD Monitoring', 'heart', route('admin.dswd.module', 'f1kd')],
        ['DSWD / 4Ps Staff', 'id-card', route('admin.dswd-staff.index')],
        ['Reports and Statistics', 'stats', route('admin.dswd.reports')],
        ['Notifications', 'bell', route('admin.dswd.module', 'notifications')],
    ] : [
        ['Dashboard', 'stats', route('admin.cho.dashboard')],
        ['Mothers and Children', 'users', route('admin.cho.module', 'mothers')],
        ['Pregnancy Monitoring', 'heart', route('admin.cho.module', 'pregnancy')],
        ['Consultation Records', 'message', route('admin.cho.module', 'consultations')],
        ['Vaccination Records', 'shield', route('admin.cho.module', 'vaccinations')],
        ['Program Staff', 'id-card', route('admin.program-staff.index')],
        ['Educational Content', 'book', route('admin.educational-content.index')],
        ['Clinical Settings', 'settings', route('admin.maternal-vital-thresholds.index')],
        ['Health Statistics and Reports', 'stats', route('admin.cho.reports')],
        ['Notifications', 'bell', route('admin.cho.module', 'notifications')],
    ];
@endphp
@foreach ($departmentLinks as [$label, $icon, $href])
    <a class="admin-nav-link {{ request()->url() === $href || ($department === 'dswd' && $label === 'F1KD Monitoring' && request()->routeIs('admin.dswd.module') && in_array(request()->route('module'), ['beneficiaries', 'f1kd', 'attendance'], true)) ? 'is-active' : '' }}" href="{{ $href }}">{!! $adminIcons[$icon] !!} {{ $label }}</a>
@endforeach
