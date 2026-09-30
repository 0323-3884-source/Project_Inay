@php
    $isAdminAuthScreen = trim($__env->yieldContent('admin_auth_screen')) !== '';
    $adminUsername = $adminUsername ?? session('admin_username', 'admin');
    $adminNotificationUserId = (int) session('admin_id');
    $adminNotificationCount = ! $isAdminAuthScreen && session('admin_authenticated') === true && $adminNotificationUserId > 0
        ? \App\Models\AppNotification::where('recipient_id', $adminNotificationUserId)
            ->where('recipient_role', 'admin')
            ->whereNull('read_at')
            ->count()
        : 0;
    $adminIcons = [
        'mark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.8-9.6-9.1C.7 8.4 2.8 4.5 6.7 4.5c2 0 3.6 1 4.5 2.5.9-1.5 2.5-2.5 4.5-2.5 3.9 0 6 3.9 4.3 7.4C19.5 16.2 12 21 12 21z"/></svg>',
        'stats' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M12 16V6"/><path d="M16 16v-4"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
        'id-card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2.4"/><path d="M6 16c.8-1.6 1.9-2.4 3-2.4s2.2.8 3 2.4"/><path d="M14 9h4"/><path d="M14 13h4"/><path d="M14 17h3"/></svg>',
        'message' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 21a2 2 0 0 0 3.4 0"/><path d="M4 17h16"/><path d="M6 17c1.2-1.2 1.8-2.7 1.8-7a4.2 4.2 0 1 1 8.4 0c0 4.3.6 5.8 1.8 7"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/><path d="M8 7h7"/><path d="M8 11h5"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2.1 2.1 0 1 1-3 3l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1.1 1.7V21a2.1 2.1 0 1 1-4.2 0v-.2a1.8 1.8 0 0 0-1.1-1.7 1.8 1.8 0 0 0-2 .4l-.1.1a2.1 2.1 0 1 1-3-3l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.7-1.1H3a2.1 2.1 0 1 1 0-4.2h.2a1.8 1.8 0 0 0 1.7-1.1 1.8 1.8 0 0 0-.4-2l-.1-.1a2.1 2.1 0 1 1 3-3l.1.1a1.8 1.8 0 0 0 2 .4h.1a1.8 1.8 0 0 0 1.1-1.7V3a2.1 2.1 0 1 1 4.2 0v.2a1.8 1.8 0 0 0 1.1 1.7h.1a1.8 1.8 0 0 0 2-.4l.1-.1a2.1 2.1 0 1 1 3 3l-.1.1a1.8 1.8 0 0 0-.4 2v.1a1.8 1.8 0 0 0 1.7 1.1h.2a2.1 2.1 0 1 1 0 4.2h-.2a1.8 1.8 0 0 0-1.7 1.1Z"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6l1.2 1.2L12 22l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>',
        'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15"/><path d="M15 6v15"/></svg>',
        'alert' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        'activity' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Project INAY')</title>
    @include('layouts.partials.management-styles')
    @stack('styles')
</head>
<body class="{{ $isAdminAuthScreen ? 'admin-auth-body' : 'admin-shell' }}">
    @if ($isAdminAuthScreen)
        @yield('content')
    @else
        <aside class="admin-sidebar" aria-label="Admin navigation">
            <div class="admin-brand">
                <span class="admin-brand-mark">{!! $adminIcons['mark'] !!}</span>
                <span>
                    <span class="admin-brand-title">Project INAY</span>
                    <span class="admin-brand-subtitle">Admin Console</span>
                </span>
            </div>
            <nav class="admin-nav">
                <p class="admin-nav-label">System</p>
                <a class="admin-nav-link {{ request()->routeIs('admin.dswd-staff.*') ? 'is-active' : '' }}" href="{{ route('admin.dswd-staff.index') }}">{!! $adminIcons['id-card'] !!} DSWD / 4Ps Staff</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.statistics') ? 'is-active' : '' }}" href="{{ route('admin.statistics') }}">
                    {!! $adminIcons['stats'] !!}
                    Statistics
                </a>
                <a class="admin-nav-link {{ request()->routeIs('admin.program-staff.*') ? 'is-active' : '' }}" href="{{ route('admin.program-staff.index') }}">
                    {!! $adminIcons['id-card'] !!}
                    Program Staff
                </a>
                <a class="admin-nav-link {{ request()->routeIs('admin.educational-content.*') ? 'is-active' : '' }}" href="{{ route('admin.educational-content.index') }}">
                    {!! $adminIcons['book'] !!}
                    Educational Content
                </a>
                <a class="admin-nav-link {{ request()->routeIs('admin.maternal-vital-thresholds.*') ? 'is-active' : '' }}" href="{{ route('admin.maternal-vital-thresholds.index') }}">
                    {!! $adminIcons['settings'] !!}
                    Clinical Settings
                </a>
                <a class="admin-nav-link {{ request()->routeIs('admin.staff-messages.*') ? 'is-active' : '' }}" href="{{ route('admin.staff-messages.index') }}">
                    {!! $adminIcons['message'] !!}
                    Admin Messages
                </a>
            </nav>
            <div
                class="admin-notification-wrap"
                data-notification-root
                data-notification-role="admin"
                data-notification-user="{{ $adminNotificationUserId }}"
                data-notifications-url="{{ route('notifications.index') }}"
                data-notification-read-url-template="{{ route('notifications.read', ['notification' => '__NOTIFICATION__']) }}"
                data-notification-read-all-url="{{ route('notifications.read-all') }}"
                data-csrf="{{ csrf_token() }}"
            >
                <button class="admin-notification-button" type="button" data-notification-toggle aria-label="Notifications">
                    {!! $adminIcons['bell'] !!}
                    Notifications
                    <span class="admin-notification-count" data-notification-count @if($adminNotificationCount === 0) hidden @endif>{{ $adminNotificationCount > 99 ? '99+' : $adminNotificationCount }}</span>
                </button>
                <div class="app-notification-menu" data-notification-menu hidden>
                    <div class="app-notification-head">
                        <strong>Notifications</strong>
                        <div class="app-notification-actions">
                            <button type="button" data-notification-enable>Enable browser alerts</button>
                            <button type="button" data-notification-mark-all>Mark all read</button>
                        </div>
                    </div>
                    <div class="app-notification-list" data-notification-list>
                        <div class="app-notification-empty">Loading notifications...</div>
                    </div>
                </div>
            </div>
            <div class="admin-sidebar-bottom">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="admin-logout" type="submit">
                        {!! $adminIcons['logout'] !!}
                        Logout
                    </button>
                </form>
            </div>
        </aside>
        <main class="admin-main">
            @yield('content')
        </main>
    @endif
    @if (! $isAdminAuthScreen)
        <script src="{{ asset('js/app-notifications.js') }}?v={{ filemtime(public_path('js/app-notifications.js')) }}" defer></script>
    @endif
    @stack('scripts')
</body>
</html>
