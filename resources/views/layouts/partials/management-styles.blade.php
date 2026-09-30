    <style>
        :root { --admin-green:#00856a; --admin-green-dark:#006854; --admin-pink:#ec008c; --admin-ink:#071127; --admin-muted:#52627d; --admin-line:#dbe5f1; --admin-soft:#f8fafc; --admin-card:#ffffff; }
        * { box-sizing: border-box; letter-spacing: 0; }
        body { margin: 0; color: var(--admin-ink); background: #f3f7fb; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
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
        .admin-notification-wrap { position: relative; padding: 0 14px 16px; }
        .admin-notification-button { position: relative; display: inline-flex; width: 100%; min-height: 44px; align-items: center; justify-content: flex-start; gap: 10px; padding: 0 14px; color: #334155; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; font-weight: 900; cursor: pointer; }
        .admin-notification-button:hover, .admin-notification-button.has-unread { color: var(--admin-green); background: #ecfdf5; border-color: #c9f2df; }
        .admin-notification-count { position: absolute; top: -6px; right: -6px; display: inline-grid; min-width: 20px; height: 20px; place-items: center; padding: 0 5px; color: #ffffff; background: var(--admin-pink); border: 2px solid #ffffff; border-radius: 999px; font-size: 10px; font-weight: 900; line-height: 1; }
        .admin-notification-count[hidden] { display: none; }
        .app-notification-menu { position: absolute; top: calc(100% - 8px); left: 14px; z-index: 90; display: grid; width: min(360px, calc(100vw - 24px)); overflow: hidden; color: #12213c; background: #ffffff; border: 1px solid #dbe5f1; border-radius: 8px; box-shadow: 0 24px 54px rgba(15, 23, 42, 0.18); }
        .app-notification-menu[hidden] { display: none; }
        .app-notification-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 13px 14px; border-bottom: 1px solid #eef2f7; }
        .app-notification-head strong { color: #071127; font-size: 14px; font-weight: 900; }
        .app-notification-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .app-notification-actions button { min-height: 30px; padding: 0 9px; color: #53647c; background: #f8fafc; border: 1px solid #dbe5f1; border-radius: 8px; font-size: 11px; font-weight: 900; cursor: pointer; }
        .app-notification-actions button:hover { color: var(--admin-green); background: #ecfdf5; border-color: #c9f2df; }
        .app-notification-list { display: grid; max-height: min(420px, calc(100vh - 180px)); overflow-y: auto; padding: 6px; }
        .app-notification-item { display: grid; gap: 4px; width: 100%; min-height: 70px; justify-items: start; padding: 10px; color: #334155; text-align: left; background: transparent; border: 1px solid transparent; border-radius: 8px; }
        .app-notification-item:hover, .app-notification-item.is-unread { background: #ecfdf5; border-color: #c9f2df; }
        .app-notification-item strong { color: #071127; font-size: 13px; font-weight: 900; line-height: 1.25; }
        .app-notification-item span { color: #52627d; font-size: 12px; font-weight: 700; line-height: 1.4; }
        .app-notification-item small, .app-notification-empty { color: #8da0b9; font-size: 11px; font-weight: 800; }
        .app-notification-empty { padding: 22px 14px; text-align: center; }
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
        .admin-summary-icon { display: grid; width: 48px; height: 48px; place-items: center; color: #047a63; background: linear-gradient(180deg, #f6fffb 0%, #ecfdf5 100%); border: 1px solid #c7eedf; border-radius: 12px; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72); }
        .admin-summary-icon svg { width: 20px; height: 20px; stroke-width: 1.65; }
        .admin-summary-card > div > span { display: block; color: #64748b; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .admin-summary-card > div > strong { display: block; margin-top: 7px; font-size: 28px; line-height: 1; font-weight: 900; }
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
