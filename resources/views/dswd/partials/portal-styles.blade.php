<style>
.dswd-shell .admin-card { background:#fff; border:1px solid var(--inay-line); border-radius:16px; padding:24px; margin-bottom:20px; min-width:0; }
.dswd-shell .admin-card h2 { margin:0 0 16px; font-size:20px; color:var(--inay-navy); }
.dswd-shell .admin-card-head { margin-bottom:18px; }
.dswd-shell .admin-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
.dswd-shell .admin-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; margin-bottom:24px; }
.dswd-shell .admin-summary-grid.f1kd-summary { grid-template-columns:repeat(3,minmax(0,1fr)); }
.dswd-shell .admin-summary-card, .dswd-shell .admin-summary-card>div { min-width:0; }
.dswd-shell .admin-summary-card div>span { display:block; overflow-wrap:anywhere; }
.dswd-shell .portal-main { min-width:0; }
.dswd-shell .dswd-filters>*, .dswd-shell .dswd-form label { min-width:0; }
.dswd-shell .dswd-table-wrap { max-width:100%; -webkit-overflow-scrolling:touch; }
.dswd-shell .dswd-table { min-width:640px; }
.dswd-shell .dswd-actions .dswd-button { white-space:normal; text-align:center; }
.dswd-shell .dswd-pagination { flex-wrap:wrap; }
.dswd-shell .admin-summary-card { display:flex; align-items:center; gap:14px; padding:24px; background:white; border:1px solid var(--inay-line); border-radius:16px; }
.dswd-shell .admin-summary-card strong { display:block; margin-top:10px; font-size:32px; color:var(--inay-navy); }
.dswd-shell .admin-summary-card div>span { font-size:13px; color:var(--inay-muted); font-weight:600; }
.dswd-shell .admin-summary-icon { display:grid; place-items:center; flex:0 0 44px; height:44px; border-radius:12px; color:var(--inay-pink); background:var(--inay-soft-pink); }
.dswd-shell .admin-summary-icon svg { width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:1.7; }
.dswd-shell .dswd-button { background:var(--inay-pink); border-color:var(--inay-pink); border-radius:12px; }
.dswd-shell .dswd-button.secondary { background:white; color:var(--inay-pink); }
.dswd-shell .dswd-filters { display:grid; }
.dswd-shell .admin-empty { padding:30px; text-align:center; color:var(--inay-muted); }
@media(max-width:1180px) {
    .dswd-shell .admin-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .dswd-shell .admin-summary-card { padding:18px; gap:10px; }
}
@media(max-width:760px) { .dswd-shell .admin-grid { grid-template-columns:1fr; } }
@media(max-width:760px) {
    .dswd-shell .admin-summary-grid.f1kd-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .dswd-shell .dswd-actions .dswd-button { flex:1 1 180px; }
}
@media(max-width:540px) {
    .dswd-shell .admin-summary-grid { gap:10px; }
    .dswd-shell .admin-summary-card { flex-direction:column; align-items:flex-start; padding:14px; gap:8px; }
    .dswd-shell .admin-summary-card div>span { font-size:12px; line-height:1.4; }
    .dswd-shell .admin-summary-card strong { font-size:26px; margin-top:6px; }
    .dswd-shell .admin-summary-icon { flex-basis:auto; width:36px; height:36px; }
    .dswd-shell .admin-card { padding:16px; }
    .dswd-shell .dswd-chart-row { grid-template-columns:minmax(0,1fr) minmax(0,1fr) 32px; gap:8px; font-size:12px; }
    .dswd-shell .account-heading h1 { overflow-wrap:anywhere; }
}
@media print {
.dswd-shell .dswd-monthly-report,
.dswd-shell .dswd-monthly-report * { font-family:Arial, Helvetica, sans-serif !important; }
.dswd-shell .dswd-monthly-report { color:#222; font-size:11pt; line-height:1.45; }
.dswd-shell .dswd-monthly-report .dswd-report-action,
.dswd-shell .dswd-monthly-report nav[role="navigation"] { display:none !important; }
.dswd-shell .dswd-monthly-report .dswd-table-wrap { overflow:visible; }
.dswd-shell .dswd-monthly-report .dswd-table { width:100%; font-size:9pt; }
.dswd-shell .dswd-monthly-report .dswd-table th,
.dswd-shell .dswd-monthly-report .dswd-table td { padding:8px 6px; color:#222; overflow-wrap:anywhere; }
.dswd-shell .dswd-monthly-report .dswd-table th { background:#f2f2f2; font-weight:700; }
.dswd-shell .dswd-monthly-report .dswd-table thead { display:table-header-group; }
.dswd-shell .dswd-monthly-report .dswd-table tr { break-inside:avoid; }
.dswd-shell .dswd-monthly-report .admin-card { break-inside:auto; }
.dswd-shell .dswd-monthly-report h2 { break-after:avoid; }
.dswd-shell .dswd-monthly-report a { color:inherit; text-decoration:none; }
.dswd-shell .dswd-monthly-report .admin-card,
.dswd-shell .dswd-monthly-report .admin-summary-card { border:0; border-radius:0; box-shadow:none; background:transparent; }
.dswd-shell .dswd-monthly-report .admin-card { padding:16px 0; }
.dswd-shell .dswd-monthly-report .admin-summary-card { padding:16px 0; }
.dswd-shell .dswd-monthly-report .admin-summary-icon { display:none; }
.dswd-shell .dswd-monthly-report .account-heading h1 { color:var(--inay-navy); }
.dswd-shell .dswd-monthly-report .dswd-note { color:#52627d; }
}
@media print { .dswd-shell .dswd-table { min-width:0; } }
@media print { .dswd-shell .portal-sidebar,.dswd-shell .portal-header { display:none; } .dswd-shell .portal-main { padding:0; } }
</style>
