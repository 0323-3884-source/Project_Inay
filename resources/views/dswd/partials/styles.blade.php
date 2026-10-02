<style>
.dswd-shell { --admin-green:#bc1368; --admin-green-dark:#950f53; --admin-soft:#fff7fb; background:#f8f6f8; }
.dswd-shell .admin-sidebar { overflow-y:auto; }
.dswd-shell .admin-brand-mark svg { fill:none; stroke:currentColor; }
.dswd-shell .admin-nav-link.is-active { background:#fff0f7; color:#ad105d; border-color:#f7c9df; }
.dswd-shell .admin-topbar { margin-bottom:24px; }
.dswd-shell .admin-summary-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
.dswd-shell .admin-summary-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
.dswd-kicker { color:#bc1368; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
.dswd-badge { display:inline-block; padding:7px 10px; background:#fff0f7; color:#9d1456; border-radius:8px; font-size:13px; }
.dswd-note { color:#52627d; font-size:13px; line-height:1.6; }
.dswd-stack { display:grid; gap:20px; }
.dswd-filters { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
.dswd-filters label,.dswd-form label { display:grid; gap:6px; font-size:13px; font-weight:700; }
.dswd-filters input,.dswd-filters select,.dswd-form input,.dswd-form select,.dswd-form textarea { width:100%; min-height:43px; padding:10px; border:1px solid #cbd8ea; border-radius:8px; font:inherit; background:white; color:#10213f; }
.dswd-actions { display:flex; flex-wrap:wrap; align-items:end; gap:10px; }
.dswd-button { display:inline-flex; align-items:center; justify-content:center; min-height:43px; padding:10px 16px; border:1px solid #bc1368; border-radius:8px; color:white; background:#bc1368; font:inherit; font-weight:700; cursor:pointer; text-decoration:none; }
.dswd-button.secondary { color:#9d1456; background:white; }
.dswd-table-wrap { overflow-x:auto; }
.dswd-table { width:100%; border-collapse:collapse; font-size:13px; }
.dswd-table th,.dswd-table td { padding:13px 12px; border-bottom:1px solid #e5e9ef; text-align:left; vertical-align:top; }
.dswd-table th { background:#f9f6f8; color:#52627d; font-weight:700; }
.dswd-table a { color:#a20e59; font-weight:700; }
.dswd-pagination { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:18px; font-size:13px; }
.dswd-chart-row { display:grid; grid-template-columns:minmax(100px,1fr) 2fr 45px; gap:12px; align-items:center; margin:14px 0; font-size:13px; }
.dswd-track { height:12px; background:#f3e7ee; border-radius:6px; overflow:hidden; }
.dswd-fill { height:100%; background:#ce337e; border-radius:6px; }
.dswd-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
.dswd-details dt { color:#52627d; font-size:13px; margin-bottom:6px; }
.dswd-details dd { margin:0; font-weight:700; overflow-wrap:anywhere; }
.dswd-form { display:grid; gap:16px; max-width:640px; }
.dswd-menu { display:none; }
.dswd-donut { width:160px; height:160px; margin:20px auto; border-radius:50%; display:grid; place-items:center; }
.dswd-donut span { width:116px; height:116px; display:grid; place-content:center; border-radius:50%; background:white; text-align:center; font-size:24px; font-weight:800; }
.dswd-donut small { font-size:12px; color:#52627d; font-weight:400; }
:focus-visible { outline:3px solid #167d95; outline-offset:3px; }
@media(max-width:1180px) { .dswd-shell .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .dswd-shell .admin-summary-grid { grid-template-columns:1fr; } }
@media(max-width:1180px) { .dswd-shell .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:640px) { .dswd-shell .admin-summary-grid { grid-template-columns:1fr; } }
@media(max-width:860px) { .dswd-menu { display:flex; justify-content:space-between; margin:10px 20px; padding:12px; border:1px solid #eadfe4; background:white; border-radius:8px; font:inherit; } .dswd-navigation { display:none; } .dswd-navigation.is-open { display:block; } .dswd-shell .admin-sidebar { height:auto; } .dswd-filters { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:540px) { .dswd-filters,.dswd-details { grid-template-columns:1fr; } .dswd-shell .admin-main { padding:16px; } }
@media print { .admin-sidebar,.dswd-filters,.dswd-actions { display:none!important; } .admin-shell { display:block; } .admin-main { padding:0; } .admin-card { box-shadow:none; break-inside:avoid; } }
.dswd-shell .portal-main, .dswd-shell .portal-main * { box-sizing:border-box; }
.f1kd-charts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
.f1kd-compliant { background:#e6f6ed; color:#17643b; }
.f1kd-verification { background:#fff3d8; color:#765000; }
.f1kd-unavailable { background:#fde9ec; color:#9d2638; }
.f1kd-not_applicable { background:#edf0f5; color:#52627d; }
.dswd-shell .admin-summary-grid.f1kd-summary { grid-template-columns:repeat(3,minmax(0,1fr)); }
@media(max-width:1180px) { .dswd-shell .admin-summary-grid.f1kd-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:540px) { .dswd-shell .admin-summary-grid.f1kd-summary { grid-template-columns:1fr; } }
@media(max-width:640px) { .f1kd-charts { grid-template-columns:1fr; } }
@media print { .portal-sidebar,.portal-topbar,.portal-sidebar-overlay,.account-heading>a { display:none!important; } .portal-main { margin:0!important; padding:0!important; } }
</style>
