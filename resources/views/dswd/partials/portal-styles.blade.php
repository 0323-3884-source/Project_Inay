<style>
.dswd-shell .admin-card { background:#fff; border:1px solid var(--inay-line); border-radius:16px; padding:24px; margin-bottom:20px; min-width:0; }
.dswd-shell .admin-card h2 { margin:0 0 16px; font-size:20px; color:var(--inay-navy); }
.dswd-shell .admin-card-head { margin-bottom:18px; }
.dswd-shell .admin-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
.dswd-shell .admin-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; margin-bottom:24px; }
.dswd-shell .admin-summary-card { display:flex; align-items:center; gap:14px; padding:24px; background:white; border:1px solid var(--inay-line); border-radius:16px; }
.dswd-shell .admin-summary-card strong { display:block; margin-top:10px; font-size:32px; color:var(--inay-navy); }
.dswd-shell .admin-summary-card div>span { font-size:13px; color:var(--inay-muted); font-weight:600; }
.dswd-shell .admin-summary-icon { display:grid; place-items:center; flex:0 0 44px; height:44px; border-radius:12px; color:var(--inay-pink); background:var(--inay-soft-pink); }
.dswd-shell .admin-summary-icon svg { width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:1.7; }
.dswd-shell .dswd-button { background:var(--inay-pink); border-color:var(--inay-pink); border-radius:12px; }
.dswd-shell .dswd-button.secondary { background:white; color:var(--inay-pink); }
.dswd-shell .dswd-filters { display:grid; }
.dswd-shell .admin-empty { padding:30px; text-align:center; color:var(--inay-muted); }
@media(max-width:1180px) { .dswd-shell .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:760px) { .dswd-shell .admin-grid { grid-template-columns:1fr; } }
@media(max-width:540px) { .dswd-shell .admin-summary-grid { grid-template-columns:1fr; } .dswd-shell .admin-card { padding:18px; } }
@media print { .dswd-shell .portal-sidebar,.dswd-shell .portal-header { display:none; } .dswd-shell .portal-main { padding:0; } }
</style>
