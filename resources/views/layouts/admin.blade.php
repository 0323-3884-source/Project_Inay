@php
    $isAdminAuthScreen = trim($__env->yieldContent('admin_auth_screen')) !== '';
    $adminUsername = $adminUsername ?? session('admin_username', 'admin');
    $adminIcons = [
        'mark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.8-9.6-9.1C.7 8.4 2.8 4.5 6.7 4.5c2 0 3.6 1 4.5 2.5.9-1.5 2.5-2.5 4.5-2.5 3.9 0 6 3.9 4.3 7.4C19.5 16.2 12 21 12 21z"/></svg>',
        'stats' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M12 16V6"/><path d="M16 16v-4"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
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
    <style>
        :root { --admin-green:#00856a; --admin-green-dark:#006854; --admin-pink:#ec008c; --admin-ink:#071127; --admin-muted:#52627d; --admin-line:#dbe5f1; --admin-soft:#f8fafc; --admin-card:#ffffff; }
        * { box-sizing: border-box; letter-spacing: 0; }
        body { margin: 0; color: var(--admin-ink); background: #f3f7fb; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        a { color: inherit; }
        .admin-auth-body { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(140deg, #f6fbf9 0%, #eef7ff 48%, #fff4fa 100%); }
        .admin-login-shell { width: min(100%, 430px); }
        .admin-auth-brand { display: grid; justify-items: center; text-align: center; margin-bottom: 22px; }
        .admin-auth-mark, .admin-brand-mark { display: grid; place-items: center; color: #ffffff; background: #071127; border-radius: 16px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14); }
        .admin-auth-mark { width: 58px; height: 58px; margin-bottom: 14px; }
        .admin-auth-mark svg { width: 28px; height: 28px; color: var(--admin-pink); fill: currentColor; stroke: none; }
        .admin-auth-title { margin: 0; font-size: 28px; font-weight: 900; }
        .admin-auth-subtitle { margin: 6px 0 0; color: var(--admin-muted); font-size: 14px; font-weight: 700; }
        .admin-auth-card, .admin-card { background: var(--admin-card); border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08); }
        .admin-auth-card { padding: 26px; }
        .admin-card-title { margin: 0; font-size: 22px; font-weight: 900; }
        .admin-card-subtitle { margin: 8px 0 22px; color: var(--admin-muted); font-size: 14px; line-height: 1.5; }
        .admin-field { display: grid; gap: 8px; margin-top: 16px; }
        .admin-label { color: #64748b; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .admin-input { width: 100%; height: 48px; padding: 0 14px; color: var(--admin-ink); background: #ffffff; border: 1px solid #cbd8ea; border-radius: 8px; font-size: 15px; outline: none; transition: border-color .18s ease, box-shadow .18s ease; }
        .admin-input:focus { border-color: var(--admin-green); box-shadow: 0 0 0 3px rgba(0, 133, 106, 0.14); }
        .admin-submit, .admin-logout, .admin-nav-link { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 46px; border-radius: 8px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .admin-submit { width: 100%; margin-top: 22px; color: #ffffff; background: var(--admin-green); border: 1px solid var(--admin-green); font-size: 15px; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
        .admin-submit:hover { background: var(--admin-green-dark); box-shadow: 0 12px 24px rgba(0, 133, 106, 0.18); transform: translateY(-1px); }
        .admin-alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 800; }
        .admin-alert.is-error { color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }
        .admin-alert.is-success { color: #007f5f; background: #ecfdf5; border: 1px solid #86efc2; }
        .admin-field-error { color: #be123c; font-size: 12px; font-weight: 800; }
        .admin-shell { min-height: 100vh; display: grid; grid-template-columns: 280px minmax(0, 1fr); }
        .admin-sidebar { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; background: #ffffff; border-right: 1px solid var(--admin-line); }
        .admin-brand { display: flex; gap: 12px; align-items: center; padding: 22px 20px; border-bottom: 1px solid #eef2f7; }
        .admin-brand-mark { width: 46px; height: 46px; border-radius: 14px; }
        .admin-brand-mark svg { color: var(--admin-pink); fill: currentColor; stroke: none; }
        .admin-brand-title { display: block; font-size: 15px; font-weight: 900; }
        .admin-brand-subtitle { display: block; margin-top: 3px; color: var(--admin-green); font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .admin-nav { padding: 18px 14px; }
        .admin-nav-label { margin: 0 0 10px 8px; color: #94a3b8; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .admin-nav-link { width: 100%; justify-content: flex-start; padding: 0 14px; color: #334155; border: 1px solid transparent; background: transparent; }
        .admin-nav-link.is-active { color: var(--admin-green); background: #ecfdf5; border-color: #c9f2df; }
        .admin-sidebar-bottom { margin-top: auto; padding: 14px; border-top: 1px solid #eef2f7; }
        .admin-logout { width: 100%; color: #be123c; background: #fff1f2; border: 1px solid #fecdd3; }
        .admin-main { min-width: 0; padding: 28px; }
        .admin-topbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 24px; }
        .admin-kicker { margin: 0 0 8px; color: var(--admin-green); font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .admin-page-title { margin: 0; font-size: 30px; line-height: 1.12; font-weight: 900; }
        .admin-page-copy { margin: 8px 0 0; color: var(--admin-muted); font-size: 15px; }
        .admin-user-chip { display: inline-flex; align-items: center; gap: 9px; padding: 10px 12px; color: #007f5f; background: #ecfdf5; border: 1px solid #c9f2df; border-radius: 999px; font-size: 13px; font-weight: 900; }
        .admin-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
        .admin-summary-card { display: grid; grid-template-columns: 48px minmax(0, 1fr); gap: 14px; align-items: center; padding: 18px; background: #ffffff; border: 1px solid var(--admin-line); border-radius: 8px; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06); animation: adminFade .26s ease both; }
        .admin-summary-icon { display: grid; width: 48px; height: 48px; place-items: center; color: var(--admin-green); background: #ecfdf5; border: 1px solid #c9f2df; border-radius: 8px; }
        .admin-summary-card span { display: block; color: #64748b; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .admin-summary-card strong { display: block; margin-top: 7px; font-size: 28px; line-height: 1; font-weight: 900; }
        .admin-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .admin-card { padding: 20px; overflow: hidden; }
        .admin-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
        .admin-card-head h2 { margin: 0; font-size: 18px; font-weight: 900; }
        .admin-card-head p { margin: 7px 0 0; color: var(--admin-muted); font-size: 13px; }
        .admin-empty { display: grid; min-height: 180px; place-items: center; color: #64748b; background: var(--admin-soft); border: 1px dashed #cbd5e1; border-radius: 8px; text-align: center; padding: 18px; }
        @keyframes adminFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1180px) { .admin-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .admin-grid { grid-template-columns: 1fr; } }
        @media (max-width: 860px) { .admin-shell { grid-template-columns: 1fr; } .admin-sidebar { position: static; height: auto; } .admin-main { padding: 20px; } .admin-topbar { align-items: flex-start; flex-direction: column; } }
        @media (max-width: 640px) { .admin-summary-grid { grid-template-columns: 1fr; } .admin-auth-body { padding: 16px; } .admin-auth-card { padding: 22px 18px; } }
    </style>
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
                <a class="admin-nav-link is-active" href="{{ route('admin.statistics') }}">
                    {!! $adminIcons['stats'] !!}
                    Statistics
                </a>
            </nav>
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
    @stack('scripts')
</body>
</html>
