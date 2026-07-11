<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Project INAY')</title>
    <style>
        :root {
            --inay-pink: #ec0a78;
            --inay-soft-pink: #fff4fa;
            --inay-button-pink: #f58fc9;
            --inay-navy: #111a32;
            --inay-text: #10213f;
            --inay-muted: #436084;
            --inay-line: #d9e3ef;
            --inay-field: #c7d6e8;
            --inay-panel: #ffffff;
            --inay-green: #0f9f6e;
            --inay-red: #d92d63;
            --portal-sidebar-width: 270px;
            --portal-header-height: 76px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: #2f2a2c;
            background: #f7f4f1;
        }

        a {
            color: #9f3f72;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 32px;
            background: #ffffff;
            border-bottom: 1px solid #eadfe4;
        }

        .brand {
            font-weight: 700;
            font-size: 20px;
            color: #8f315f;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .page {
            width: min(960px, calc(100% - 32px));
            margin: 32px auto;
        }

        .card {
            background: #ffffff;
            border: 1px solid #eadfe4;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(75, 54, 64, 0.08);
        }

        .narrow {
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }

        h1, h2 {
            margin-top: 0;
            color: #8f315f;
        }

        p {
            line-height: 1.6;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .button, button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            min-height: 42px;
            font-weight: 700;
            background: #a43f72;
            color: #ffffff;
            cursor: pointer;
        }

        .button.secondary, button.secondary {
            background: #f1e5eb;
            color: #8f315f;
        }

        .button:hover {
            text-decoration: none;
        }

        form {
            margin: 0;
        }

        .form-grid {
            display: grid;
            gap: 16px;
        }

        .two-columns {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
        }

        input, select {
            width: 100%;
            border: 1px solid #d7c5ce;
            border-radius: 8px;
            padding: 11px 12px;
            font: inherit;
            background: #ffffff;
        }

        input:focus, select:focus {
            outline: 2px solid #e8bfd3;
            border-color: #a43f72;
        }

        .field-error {
            display: block;
            margin-top: 6px;
            color: #b42318;
            font-size: 14px;
        }

        .alert {
            margin-bottom: 18px;
            border-radius: 8px;
            padding: 12px 14px;
            border: 1px solid #d8c7a7;
            background: #fff7df;
        }

        .alert.error {
            border-color: #efc3bd;
            background: #fff0ee;
            color: #8f1d18;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .detail-item {
            padding: 14px;
            border: 1px solid #eadfe4;
            border-radius: 8px;
            background: #fbf9f8;
        }

        .detail-label {
            display: block;
            margin-bottom: 4px;
            color: #6f5f68;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        body.portal-shell {
            color: #12213c;
            background: #f8fafc;
        }

        .portal-drawer-check {
            position: fixed;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .portal-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 40;
            display: flex;
            width: var(--portal-sidebar-width);
            max-width: 88vw;
            flex-direction: column;
            background: #ffffff;
            border-right: 1px solid #e3eaf3;
            box-shadow: 14px 0 30px rgba(15, 23, 42, 0.06);
            transition: transform 200ms ease;
        }

        .portal-header {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--portal-sidebar-width);
            z-index: 35;
            display: flex;
            min-width: 0;
            height: var(--portal-header-height);
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 32px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid #e3eaf3;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(12px);
        }

        .portal-main {
            width: auto;
            margin: 0;
            padding: 108px 34px 42px calc(var(--portal-sidebar-width) + 34px);
        }

        .portal-brand {
            display: flex;
            min-width: 0;
            height: 88px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 0 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .portal-brand-link {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 12px;
            color: #0b1220;
        }

        .portal-brand-link:hover {
            text-decoration: none;
        }

        .portal-mark {
            display: inline-grid;
            width: 50px;
            height: 50px;
            flex: 0 0 auto;
            place-items: center;
            color: var(--inay-pink);
            background: #0f172a;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(236, 10, 120, 0.18);
        }

        body.portal-staff .portal-mark {
            background: #ffffff;
            border: 1px solid #ffd4e7;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .portal-mark svg,
        .portal-icon svg,
        .portal-header-icon svg,
        .portal-action svg,
        .portal-close svg,
        .portal-menu-button svg,
        .portal-logout-button svg {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        body.portal-mother .portal-mark svg {
            fill: currentColor;
        }

        .portal-brand-kicker,
        .portal-brand-subtitle,
        .portal-sidebar-label,
        .portal-page-kicker,
        .portal-role-badge,
        .portal-status-badge,
        .portal-pregnancy-badge {
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .portal-brand-kicker {
            margin: 0;
            color: var(--inay-pink);
            font-size: 10px;
            font-weight: 900;
        }

        .portal-brand-title {
            margin: 0;
            overflow: hidden;
            color: #050b18;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .portal-brand-subtitle {
            margin: 2px 0 0;
            overflow: hidden;
            color: var(--inay-pink);
            font-size: 10px;
            font-weight: 900;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .portal-close,
        .portal-menu-button,
        .portal-header-icon {
            position: relative;
            display: inline-flex;
            width: 44px;
            height: 44px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            color: #53647c;
            background: #ffffff;
            border: 1px solid #dfe7f1;
            border-radius: 14px;
            cursor: pointer;
            transition: color 180ms ease, background 180ms ease, border-color 180ms ease, transform 180ms ease;
        }

        .portal-close:hover,
        .portal-menu-button:hover,
        .portal-header-icon:hover {
            color: var(--inay-pink);
            background: var(--inay-soft-pink);
            border-color: #ffcfe4;
            text-decoration: none;
        }

        .portal-notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            display: inline-grid;
            min-width: 20px;
            height: 20px;
            place-items: center;
            padding: 0 5px;
            color: #ffffff;
            background: var(--inay-pink);
            border: 2px solid #ffffff;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            line-height: 1;
        }

        .portal-close:active,
        .portal-menu-button:active,
        .portal-header-icon:active,
        .portal-profile-summary:active {
            transform: scale(0.96);
        }

        .portal-close:focus-visible,
        .portal-menu-button:focus-visible,
        .portal-header-icon:focus-visible,
        .portal-profile-summary:focus-visible,
        .portal-nav-item:focus-visible,
        .portal-logout-button:focus-visible,
        .portal-dropdown-item:focus-visible {
            outline: 3px solid rgba(236, 10, 120, 0.18);
            outline-offset: 3px;
        }

        .portal-close {
            display: none;
        }

        .portal-profile-card {
            margin: 16px;
            padding: 14px;
            background: #ecfdf5;
            border: 1px solid #c9f2df;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .portal-profile-row {
            display: flex;
            min-width: 0;
            gap: 12px;
            align-items: flex-start;
        }

        .portal-avatar {
            display: inline-grid;
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
            place-items: center;
            color: var(--inay-pink);
            background: #fff0f8;
            border: 1px solid #ffd4e7;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 900;
        }

        body.portal-staff .portal-avatar {
            color: #ffffff;
            background: #111827;
            border-color: #111827;
        }

        .portal-profile-name {
            margin: 0;
            overflow: hidden;
            color: #071127;
            font-size: 15px;
            font-weight: 900;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .portal-status-line {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            color: #0f7d5a;
            font-size: 12px;
            font-weight: 800;
        }

        .portal-status-line svg {
            width: 14px;
            height: 14px;
            flex: 0 0 auto;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .portal-pregnancy-badge {
            display: inline-flex;
            max-width: 100%;
            margin-top: 9px;
            padding: 5px 10px;
            color: #b01564;
            background: #ffffff;
            border: 1px solid #ffd4e7;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
        }

        .portal-sidebar-label {
            margin: 8px 18px 10px;
            color: #8da0b9;
            font-size: 10px;
            font-weight: 900;
        }

        .portal-nav {
            min-height: 0;
            flex: 1;
            overflow-y: auto;
            padding: 0 12px 18px;
        }

        .portal-nav-list {
            display: grid;
            gap: 7px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .portal-nav-item {
            position: relative;
            display: flex;
            min-height: 50px;
            align-items: center;
            gap: 12px;
            padding: 12px 14px 12px 20px;
            color: #506079;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 900;
            transition: color 180ms ease, background 180ms ease, box-shadow 180ms ease, transform 180ms ease;
        }

        .portal-nav-item:hover {
            color: var(--inay-pink);
            background: #fff3fa;
            text-decoration: none;
            transform: translateX(2px);
        }

        .portal-nav-item:active {
            transform: translateX(2px) scale(0.985);
            box-shadow: inset 0 0 0 1px #ffd4e7;
        }

        .portal-nav-item.is-active {
            color: var(--inay-pink);
            background: #fff0f8;
            box-shadow: inset 0 0 0 1px #ffd4e7, 0 8px 18px rgba(236, 10, 120, 0.08);
        }

        body.portal-staff .portal-nav-item.is-active {
            background: #ffffff;
            box-shadow: inset 0 0 0 1px #ffd4e7, 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .portal-nav-item::before {
            position: absolute;
            left: 8px;
            width: 4px;
            height: 26px;
            content: "";
            background: transparent;
            border-radius: 999px;
            transition: background 180ms ease;
        }

        .portal-nav-item.is-active::before {
            background: var(--inay-pink);
        }

        .portal-nav-item.is-disabled {
            color: #7f8fa6;
            cursor: not-allowed;
            opacity: 0.92;
        }

        .portal-nav-item.is-disabled:hover {
            background: transparent;
            transform: none;
        }

        .portal-nav-item.is-disabled:active {
            transform: none;
            box-shadow: none;
        }

        .portal-icon {
            display: inline-flex;
            flex: 0 0 auto;
            color: currentColor;
            transition: transform 180ms ease;
        }

        .portal-nav-item:hover .portal-icon {
            transform: scale(1.08);
        }

        .portal-sidebar-bottom {
            display: grid;
            gap: 12px;
            padding: 14px;
            background: #ffffff;
            border-top: 1px solid #eef2f7;
        }

        body.portal-staff .portal-sidebar-bottom {
            padding: 16px;
        }

        .portal-logout-form,
        .portal-dropdown-form {
            margin: 0;
        }

        .portal-logout-button {
            width: 100%;
            min-height: 48px;
            justify-content: flex-start;
            gap: 12px;
            padding: 0 18px;
            color: var(--inay-pink);
            background: #fff1f6;
            border: 1px solid #ffd0e4;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 900;
            box-shadow: none;
            transition: color 180ms ease, background 180ms ease, border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
        }

        .portal-logout-button:hover {
            color: #ffffff;
            background: var(--inay-pink);
            border-color: var(--inay-pink);
            box-shadow: 0 10px 22px rgba(236, 10, 120, 0.22);
        }

        .portal-logout-button:active {
            transform: scale(0.985);
            box-shadow: 0 4px 12px rgba(236, 10, 120, 0.18);
        }

        .portal-header-left {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 14px;
        }

        .portal-menu-button {
            display: none;
        }

        .portal-page-kicker {
            margin: 0;
            color: #8a99ad;
            font-size: 10px;
            font-weight: 900;
        }

        .portal-page-title {
            margin: 1px 0 0;
            overflow: hidden;
            color: #061125;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.15;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .portal-header-right {
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .portal-status-badge {
            display: inline-flex;
            max-width: 300px;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            color: #087653;
            background: #ecfdf5;
            border: 1px solid #c9f2df;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .portal-status-badge svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .portal-header-profile {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 9px;
        }

        .portal-header-name {
            overflow: hidden;
            max-width: 190px;
            color: #101828;
            font-size: 13px;
            font-weight: 900;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .portal-role-badge {
            display: inline-flex;
            max-width: 160px;
            padding: 3px 8px;
            color: var(--inay-pink);
            background: #fff0f8;
            border: 1px solid #ffd4e7;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
        }

        .portal-profile-menu {
            position: relative;
        }

        .portal-profile-summary {
            display: inline-flex;
            width: 36px;
            height: 36px;
            align-items: center;
            justify-content: center;
            color: #667085;
            background: #ffffff;
            border: 1px solid #e3eaf3;
            border-radius: 999px;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            transition: color 180ms ease, background 180ms ease, border-color 180ms ease, transform 180ms ease;
        }

        .portal-profile-summary::-webkit-details-marker {
            display: none;
        }

        .portal-profile-menu[open] .portal-profile-summary,
        .portal-profile-summary:hover {
            color: var(--inay-pink);
            background: #fff3fa;
            border-color: #ffd4e7;
        }

        .portal-profile-summary svg {
            width: 17px;
            height: 17px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .portal-dropdown {
            position: absolute;
            top: 44px;
            right: 0;
            display: grid;
            width: 190px;
            gap: 4px;
            padding: 8px;
            background: #ffffff;
            border: 1px solid #e3eaf3;
            border-radius: 18px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
        }

        .portal-dropdown-item {
            display: flex;
            width: 100%;
            min-height: 38px;
            align-items: center;
            justify-content: flex-start;
            gap: 9px;
            padding: 9px 10px;
            color: #53647c;
            background: transparent;
            border: 0;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            box-shadow: none;
            transition: color 180ms ease, background 180ms ease, transform 180ms ease;
        }

        .portal-dropdown-item:hover {
            color: var(--inay-pink);
            background: #fff3fa;
        }

        .portal-dropdown-item:active {
            transform: scale(0.985);
        }

        .portal-dropdown-item.is-danger {
            color: #c3295d;
        }

        .portal-dropdown-item.is-muted {
            cursor: default;
            opacity: 0.75;
        }

        .portal-dropdown-item svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .casefiles-shell {
            display: grid;
            gap: 22px;
            width: min(1500px, 100%);
            margin: 0 auto;
            color: #10213f;
        }

        .casefiles-shell svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .casefiles-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 22px;
            padding-bottom: 20px;
            border-bottom: 1px solid #cad6e5;
        }

        .casefiles-heading p,
        .casefiles-heading h1,
        .casefiles-heading span,
        .casefile-card h2,
        .casefile-detail-hero h1,
        .casefile-panel h2 {
            margin: 0;
        }

        .casefiles-heading p {
            color: #8da0b9;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefiles-heading p strong {
            display: inline;
            margin: 0 8px;
            color: #c8d3e2;
        }

        .casefiles-heading h1,
        .casefile-detail-hero h1 {
            margin-top: 6px;
            color: #061125;
            font-size: 30px;
            font-weight: 900;
            line-height: 1.08;
        }

        .casefiles-heading span,
        .casefile-detail-hero p,
        .casefile-panel-note {
            display: block;
            margin-top: 8px;
            color: #53647c;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .casefiles-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(130px, 1fr));
            gap: 12px;
        }

        .casefiles-stats article,
        .casefile-card,
        .casefile-panel,
        .casefiles-empty,
        .casefile-detail-hero {
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .casefiles-stats article {
            padding: 14px 16px;
        }

        .casefiles-stats strong {
            display: block;
            color: #ec0a78;
            font-size: 24px;
            font-weight: 900;
            line-height: 1;
        }

        .casefiles-stats span,
        .casefile-facts dt,
        .casefile-vital-grid span,
        .casefile-summary-strip span {
            color: #8da0b9;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefiles-filter-form {
            display: grid;
            grid-template-columns: minmax(280px, 1.2fr) minmax(220px, 0.55fr) minmax(220px, 0.55fr) auto;
            gap: 14px;
            align-items: center;
        }

        .casefiles-search {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 12px;
            min-height: 54px;
            padding: 0 16px;
            color: #8aa0bc;
            background: #ffffff;
            border: 1px solid #cdd9e8;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .casefiles-search:focus-within {
            color: #ec0a78;
            border-color: #ff72b7;
            box-shadow: 0 0 0 4px rgba(236, 10, 120, 0.1);
        }

        .casefiles-search input,
        .casefiles-filter-form select {
            width: 100%;
            min-width: 0;
            min-height: 52px;
            color: #172033;
            background: #ffffff;
            border: 0;
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            outline: none;
        }

        .casefiles-filter-form select {
            padding: 0 14px;
            border: 1px solid #cdd9e8;
            border-radius: 10px;
        }

        .casefiles-filter-form button,
        .casefiles-filter-form a,
        .casefile-add-button,
        .casefile-primary-action,
        .casefile-icon-action,
        .casefile-back-link {
            display: inline-flex;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            color: #ffffff;
            background: #ec0a78;
            border: 1px solid #ec0a78;
            border-radius: 10px;
            padding: 0 18px;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            transition: transform 180ms ease, background 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
        }

        .casefiles-filter-form a,
        .casefile-back-link {
            color: #40536f;
            background: #ffffff;
            border-color: #cdd9e8;
        }

        .casefiles-filter-form button:hover,
        .casefile-add-button:hover,
        .casefile-primary-action:hover {
            color: #ffffff;
            background: #d80b78;
            border-color: #d80b78;
            box-shadow: 0 10px 22px rgba(236, 10, 120, 0.18);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .casefile-add-button {
            min-width: 230px;
            border-radius: 8px;
        }

        .casefiles-filter-form a:hover,
        .casefile-back-link:hover {
            color: #ec0a78;
            background: #fff3fa;
            border-color: #ffd4e7;
            text-decoration: none;
        }

        .casefiles-result-line {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #53647c;
            font-size: 13px;
            font-weight: 900;
        }

        .casefiles-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(280px, 1fr));
            gap: 18px;
        }

        .casefile-card {
            display: grid;
            gap: 16px;
            padding: 24px;
        }

        .casefile-card-top,
        .casefile-detail-profile,
        .casefile-card-actions,
        .casefile-contact-row {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 12px;
        }

        .casefile-card-top {
            align-items: flex-start;
        }

        .casefile-avatar {
            display: inline-grid;
            width: 54px;
            height: 54px;
            flex: 0 0 auto;
            place-items: center;
            color: #ec0a78;
            background: #fff0f8;
            border: 1px solid #ffd4e7;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 900;
        }

        .casefile-avatar.is-large {
            width: 86px;
            height: 86px;
            font-size: 25px;
        }

        .casefile-card h2 {
            color: #061125;
            font-size: 20px;
            font-weight: 900;
            line-height: 1.15;
        }

        .casefile-card p {
            margin: 5px 0 0;
            color: #53647c;
            font-size: 13px;
            font-weight: 800;
        }

        .casefile-risk {
            display: inline-flex;
            margin-left: auto;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .casefile-risk.is-low {
            color: #008a61;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
        }

        .casefile-risk.is-medium {
            color: #b45309;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .casefile-risk.is-high {
            color: #dc2626;
            background: #fff1f2;
            border: 1px solid #fecaca;
        }

        .casefile-risk.is-pending {
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
        }

        .casefile-contact-row {
            flex-wrap: wrap;
            color: #607089;
            font-size: 13px;
            font-weight: 800;
        }

        .casefile-contact-row span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .casefile-facts {
            display: grid;
            gap: 11px;
            margin: 0;
            padding-top: 16px;
            border-top: 1px solid #e8eef5;
        }

        .casefile-facts div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .casefile-facts dd {
            margin: 0;
            color: #061125;
            font-size: 13px;
            font-weight: 900;
            text-align: right;
        }

        .casefile-card-actions {
            align-items: stretch;
        }

        .casefile-primary-action {
            flex: 1;
        }

        .casefile-icon-action {
            width: 50px;
            flex: 0 0 auto;
            color: #ec0a78;
            background: #ffffff;
            border-color: #dbe5f0;
            padding: 0;
        }

        .casefile-icon-action:hover {
            color: #ffffff;
            background: #ec0a78;
            border-color: #ec0a78;
            text-decoration: none;
        }

        .casefile-icon-action.is-disabled {
            color: #b8c4d4;
            cursor: not-allowed;
        }

        .casefiles-empty {
            padding: 36px;
            text-align: center;
        }

        .casefiles-empty strong {
            color: #061125;
            font-size: 20px;
            font-weight: 900;
        }

        .casefiles-empty p {
            margin: 8px 0 0;
            color: #607089;
            font-weight: 700;
        }

        .casefile-detail-hero,
        .casefile-panel {
            padding: 24px;
        }

        .casefile-detail-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }

        .casefile-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 18px;
        }

        .casefile-panel h2 {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #061125;
            font-size: 19px;
            font-weight: 900;
        }

        .casefile-facts.is-detail {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding-top: 18px;
            border-top: 0;
        }

        .casefile-facts.is-detail div {
            display: block;
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
        }

        .casefile-facts.is-detail dd {
            margin-top: 7px;
            text-align: left;
            overflow-wrap: anywhere;
        }

        .casefile-vital-grid,
        .casefile-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .casefile-vital-grid article,
        .casefile-summary-strip article {
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
        }

        .casefile-vital-grid strong,
        .casefile-summary-strip strong {
            display: block;
            margin-top: 9px;
            color: #061125;
            font-size: 18px;
            font-weight: 900;
        }

        .casefile-panel-note {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .casefile-upload-list {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .casefile-upload-list article {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
        }

        .casefile-upload-list strong {
            color: #061125;
            font-weight: 900;
        }

        .casefile-upload-list p {
            margin: 4px 0 0;
            color: #607089;
            font-size: 13px;
            font-weight: 700;
        }

        body.has-casefile-modal,
        body.has-monitoring-history-modal {
            overflow: hidden;
        }

        .casefile-modal[hidden] {
            display: none;
        }

        .casefile-modal {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: grid;
            place-items: center;
            padding: 34px;
        }

        .casefile-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.68);
        }

        .casefile-modal-dialog {
            position: relative;
            z-index: 1;
            display: grid;
            width: min(1280px, calc(100vw - 48px));
            max-height: calc(100vh - 64px);
            grid-template-rows: auto auto auto minmax(0, 1fr) auto;
            overflow: hidden;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 28px 80px rgba(3, 7, 18, 0.34);
        }

        .casefile-modal-header,
        .casefile-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 28px 34px 16px;
        }

        .casefile-modal-title {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #0f9f6e;
        }

        .casefile-modal-title h2 {
            margin: 0;
            color: #111827;
            font-size: 30px;
            font-weight: 900;
            line-height: 1;
        }

        .casefile-modal-header p {
            margin: 8px 0 0;
            color: #607089;
            font-size: 15px;
            font-weight: 700;
        }

        .casefile-modal-close {
            width: 44px;
            height: 44px;
            color: #607089;
            background: #ffffff;
            border: 0;
            border-radius: 999px;
            padding: 0;
        }

        .casefile-modal-close:hover {
            color: #ec0a78;
            background: #fff0f8;
        }

        .casefile-modal-search {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0 34px 16px;
            min-height: 72px;
            padding: 0 20px;
            color: #8aa0bc;
            background: #ffffff;
            border: 1px solid #ff72b7;
            border-radius: 12px;
            box-shadow: 0 0 0 4px rgba(236, 10, 120, 0.1);
        }

        .casefile-modal-search input {
            min-width: 0;
            width: 100%;
            border: 0;
            outline: 0;
            color: #172033;
            font: inherit;
            font-size: 16px;
            font-weight: 800;
        }

        .casefile-modal-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 34px 18px;
            padding: 16px 20px;
            color: #1e40af;
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
        }

        .casefile-modal-info p {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.5;
        }

        .casefile-patient-list {
            display: grid;
            gap: 12px;
            min-height: 220px;
            overflow-y: auto;
            padding: 20px 34px;
            border-top: 1px solid #e3eaf3;
            border-bottom: 1px solid #e3eaf3;
        }

        .casefile-patient-row {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            padding: 20px 22px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
        }

        .casefile-patient-row:hover {
            border-color: #ff72b7;
            box-shadow: 0 12px 28px rgba(236, 10, 120, 0.09);
            transform: translateY(-1px);
        }

        .casefile-patient-row.is-added {
            background: #f8fafc;
            cursor: default;
            opacity: 0.75;
        }

        .casefile-patient-content {
            display: grid;
            flex: 1;
            min-width: 0;
            gap: 10px;
        }

        .casefile-patient-name {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            color: #111827;
            font-size: 20px;
            font-weight: 900;
        }

        .casefile-patient-name em {
            padding: 6px 10px;
            color: #8da0b9;
            background: #eef2f7;
            border-radius: 6px;
            font-size: 12px;
            font-style: normal;
            font-weight: 900;
        }

        .casefile-patient-meta,
        .casefile-patient-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            color: #7b8aa1;
            font-size: 14px;
            font-weight: 700;
        }

        .casefile-patient-meta span,
        .casefile-patient-tags span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .casefile-patient-tags span:first-child {
            color: #ec0a78;
            font-weight: 900;
        }

        .casefile-patient-row input[type="checkbox"] {
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            accent-color: #ec0a78;
            cursor: pointer;
        }

        .casefile-patient-row input[type="checkbox"]:disabled {
            cursor: not-allowed;
        }

        .casefile-modal-empty {
            margin: 18px 0;
            color: #607089;
            font-weight: 800;
            text-align: center;
        }

        .casefile-modal-footer {
            padding: 20px 34px;
        }

        .casefile-modal-footer strong {
            color: #607089;
            font-size: 14px;
            font-weight: 900;
        }

        .casefile-modal-footer div {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .casefile-modal-secondary,
        .casefile-modal-submit {
            display: inline-flex;
            min-height: 54px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 26px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 900;
            transition: background 180ms ease, border-color 180ms ease, color 180ms ease, transform 180ms ease;
        }

        .casefile-modal-secondary {
            color: #23324a;
            background: #ffffff;
            border: 1px solid #cdd9e8;
        }

        .casefile-modal-submit {
            color: #ffffff;
            background: #ec0a78;
            border: 1px solid #ec0a78;
        }

        .casefile-modal-submit:disabled {
            background: #f6a0cf;
            border-color: #f6a0cf;
            cursor: not-allowed;
        }

        .casefile-modal-secondary:hover,
        .casefile-modal-submit:not(:disabled):hover {
            transform: translateY(-1px);
        }

        .casefile-summary-page {
            gap: 24px;
        }

        .casefile-detail-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 22px;
            border-bottom: 1px solid #cad6e5;
        }

        .casefile-detail-heading h1,
        .casefile-detail-heading p {
            margin: 0;
        }

        .casefile-detail-heading h1 {
            margin-top: 14px;
            color: #061125;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.05;
        }

        .casefile-detail-heading p {
            margin-top: 8px;
            color: #53647c;
            font-size: 15px;
            font-weight: 700;
        }

        .casefile-breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            color: #8da0b9;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefile-breadcrumb a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #ec0a78;
        }

        .casefile-profile-card {
            display: grid;
            gap: 24px;
            padding: 22px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .casefile-profile-main {
            display: flex;
            align-items: center;
            gap: 22px;
            min-width: 0;
        }

        .casefile-avatar.is-xl {
            width: 112px;
            height: 112px;
            color: #ffffff;
            background: #ec0a78;
            border: 6px solid #ffe0ef;
            font-size: 28px;
        }

        .casefile-profile-title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .casefile-profile-title h2 {
            margin: 0;
            color: #061125;
            font-size: 27px;
            font-weight: 900;
            line-height: 1.1;
        }

        .casefile-profile-main strong {
            display: block;
            margin-top: 8px;
            color: #ec0a78;
            font-size: 15px;
            font-weight: 900;
        }

        .casefile-profile-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: -94px;
        }

        .casefile-profile-actions button,
        .casefile-panel-title button {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            color: #1f314d;
            background: #ffffff;
            border: 1px solid #cdd9e8;
            border-radius: 8px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 900;
            transition: background 180ms ease, border-color 180ms ease, color 180ms ease, transform 180ms ease;
        }

        .casefile-profile-actions button:hover,
        .casefile-panel-title button:hover {
            color: #ec0a78;
            background: #fff3fa;
            border-color: #ffd4e7;
            transform: translateY(-1px);
        }

        .casefile-profile-actions button.is-dark {
            color: #ffffff;
            background: #050a1d;
            border-color: #050a1d;
        }

        .casefile-profile-actions button.is-dark:hover {
            color: #ffffff;
            background: #ec0a78;
            border-color: #ec0a78;
        }

        .casefile-profile-facts {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px 46px;
            margin: 0;
            padding-top: 24px;
            border-top: 1px solid #e8eef5;
        }

        .casefile-profile-facts dt,
        .casefile-status-grid span,
        .casefile-progress-cards span,
        .casefile-chart-card > span,
        .casefile-section-kicker {
            color: #8da0b9;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefile-profile-facts dd {
            margin: 8px 0 0;
            color: #061125;
            font-size: 15px;
            font-weight: 900;
        }

        .casefile-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .casefile-status-grid article {
            position: relative;
            min-height: 132px;
            overflow: hidden;
            padding: 22px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .casefile-status-grid article.is-pink {
            color: #d80b78;
            background: #fff4fa;
            border-color: #ffb8db;
        }

        .casefile-status-grid article.is-green {
            color: #008a61;
            background: #ecfdf5;
            border-color: #8cecc0;
        }

        .casefile-status-grid strong {
            display: block;
            margin-top: 14px;
            color: currentColor;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.05;
        }

        .casefile-status-grid small {
            display: block;
            margin-top: 8px;
            color: currentColor;
            font-size: 13px;
            font-weight: 700;
        }

        .casefile-status-grid svg {
            position: absolute;
            top: 22px;
            right: 22px;
        }

        .casefile-status-grid em,
        .casefile-progress-cards em {
            display: block;
            height: 8px;
            margin-top: 18px;
            overflow: hidden;
            background: #eef2f7;
            border-radius: 999px;
        }

        .casefile-status-grid em::before,
        .casefile-progress-cards em::before {
            display: block;
            width: var(--progress);
            height: 100%;
            content: "";
            background: #0f9f6e;
            border-radius: inherit;
        }

        .casefile-tabs {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            padding: 8px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .casefile-tabs button {
            min-height: 44px;
            color: #324663;
            background: transparent;
            border: 0;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 900;
            transition: background 180ms ease, color 180ms ease, transform 180ms ease;
        }

        .casefile-tabs button.is-active,
        .casefile-tabs button:hover {
            color: #ffffff;
            background: #ec0a78;
        }

        .casefile-tabs button:active {
            transform: scale(0.98);
        }

        .casefile-tabs span {
            display: inline-flex;
            min-width: 22px;
            height: 22px;
            align-items: center;
            justify-content: center;
            margin-left: 6px;
            color: #607089;
            background: #eef2f7;
            border-radius: 999px;
            font-size: 12px;
        }

        .casefile-tabs button.is-active span {
            color: #ec0a78;
            background: #ffffff;
        }

        .casefile-panel-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid #061125;
        }

        .casefile-panel-title h2,
        .casefile-panel-title p {
            margin: 0;
        }

        .casefile-panel-title p,
        .casefile-section-subtitle {
            color: #53647c;
            font-size: 13px;
            font-weight: 700;
        }

        .casefile-vital-grid.is-large {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .casefile-vital-grid.is-large article {
            position: relative;
            min-height: 136px;
            padding: 20px 20px 16px;
            background: #ffffff;
        }

        .casefile-vital-grid i {
            display: inline-grid;
            width: 38px;
            height: 38px;
            place-items: center;
            margin-right: 14px;
            border-radius: 8px;
            vertical-align: middle;
        }

        .casefile-vital-grid i.is-pink {
            color: #ec0a78;
            background: #fff0f8;
            border: 1px solid #ffd4e7;
        }

        .casefile-vital-grid i.is-green {
            color: #0f9f6e;
            background: #ecfdf5;
            border: 1px solid #c9f2df;
        }

        .casefile-vital-grid i.is-blue {
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .casefile-vital-grid.is-large strong {
            font-size: 25px;
        }

        .casefile-vital-grid small {
            display: block;
            margin-top: 12px;
            padding-top: 12px;
            color: #53647c;
            border-top: 1px solid #dbe5f0;
            font-size: 12px;
            font-weight: 900;
        }

        .casefile-vital-grid b {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 6px 10px;
            color: #008a61;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .casefile-safe-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding: 14px 16px;
            color: #008a61;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 900;
        }

        .casefile-progress-section {
            display: grid;
            gap: 12px;
            margin-top: 22px;
        }

        .casefile-progress-section p,
        .casefile-progress-section h2,
        .casefile-progress-section > span,
        .casefile-section-kicker,
        .casefile-section-subtitle {
            margin: 0;
        }

        .casefile-progress-section p,
        .casefile-section-kicker {
            color: #ec0a78;
        }

        .casefile-progress-section h2 {
            color: #061125;
            font-size: 22px;
            font-weight: 900;
        }

        .casefile-progress-section > span {
            color: #53647c;
            font-size: 13px;
            font-weight: 700;
        }

        .casefile-progress-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 8px;
        }

        .casefile-progress-cards article {
            position: relative;
            min-height: 126px;
            padding: 18px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .casefile-progress-cards strong {
            display: block;
            margin-top: 12px;
            color: #061125;
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
        }

        .casefile-progress-cards small {
            display: block;
            margin-top: 9px;
            color: #53647c;
            font-size: 12px;
            font-weight: 700;
        }

        .casefile-progress-cards svg {
            position: absolute;
            top: 18px;
            right: 18px;
            color: #53647c;
        }

        .casefile-progress-cards em::before {
            background: #1f314d;
        }

        .casefile-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .casefile-chart-card {
            padding: 22px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        }

        .casefile-chart-card h3 {
            margin: 8px 0 18px;
            color: #061125;
            font-size: 19px;
            font-weight: 600;
        }

        .casefile-chart-card svg {
            width: 100%;
            height: auto;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 8px;
        }

        .casefile-chart-card svg .axis {
            stroke: #cbd5e1;
            stroke-width: 1.4;
        }

        .casefile-chart-card svg .grid {
            stroke: #dce5f0;
            stroke-dasharray: 5 8;
            stroke-width: 1;
        }

        .casefile-chart-card text {
            fill: #8090a9;
            stroke: none !important;
            stroke-width: 0 !important;
            paint-order: fill;
            font-size: 10px;
            font-weight: 400;
            text-shadow: none !important;
        }

        .casefile-chart-card text.empty {
            fill: #64748b;
            font-size: 12px;
            font-weight: 400;
        }

        .weight-dot {
            fill: #dd2b7c;
            stroke: #172033;
            stroke-width: 1.5;
        }

        .weight-line {
            fill: none;
            stroke: #dd2b7c;
            stroke-width: 2;
        }

        .systolic-line {
            fill: none;
            stroke: #dc2626;
            stroke-width: 2;
        }

        .diastolic-line {
            fill: none;
            stroke: #2563eb;
            stroke-width: 2;
        }

        .systolic-dot {
            fill: #dc2626;
            stroke: #172033;
            stroke-width: 1.5;
        }

        .diastolic-dot {
            fill: #2563eb;
            stroke: #172033;
            stroke-width: 1.5;
        }

        .casefile-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #52627a;
            font-size: 12px;
            font-weight: 500;
        }

        .casefile-legend span {
            width: 12px;
            height: 12px;
            border-radius: 999px;
        }

        .casefile-legend .is-pink {
            background: #dd2b7c;
        }

        .casefile-legend .is-red {
            background: #dc2626;
        }

        .casefile-legend .is-blue {
            background: #2563eb;
        }

        .monitoring-history-trigger {
            display: flex;
            width: 100%;
            min-height: 52px;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 14px;
            padding: 0 18px;
            color: #ec0a78;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
        }

        .monitoring-history-trigger:hover {
            border-color: #ff8bc4;
            box-shadow: 0 12px 24px rgba(236, 10, 120, 0.12);
            transform: translateY(-1px);
        }

        .monitoring-history-trigger svg {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
        }

        .monitoring-history-trigger span {
            flex: 1;
            color: inherit;
            text-align: center;
            font-weight: 500;
        }

        .monitoring-history-trigger b {
            flex: 0 0 auto;
            color: #40536f;
            background: #f8fafc;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .history-modal[hidden] {
            display: none;
        }

        .history-modal {
            position: fixed;
            inset: 0;
            z-index: 96;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .history-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.62);
            backdrop-filter: blur(4px);
        }

        .history-dialog {
            position: relative;
            z-index: 1;
            display: flex;
            width: min(850px, calc(100vw - 24px));
            max-height: 85vh;
            flex-direction: column;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
            overflow: hidden;
        }

        .history-dialog-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 22px 24px 18px;
            border-bottom: 1px solid #e7edf5;
        }

        .history-dialog-kicker {
            display: block;
            margin-bottom: 6px;
            color: #ec0a78;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .history-dialog-title {
            margin: 0;
            color: #071126;
            font-size: 22px;
            font-weight: 600;
            line-height: 1.2;
        }

        .history-dialog-copy {
            margin: 8px 0 0;
            color: #53647c;
            font-size: 13px;
            font-weight: 400;
            line-height: 1.5;
        }

        .history-close {
            display: inline-grid;
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            place-items: center;
            color: #607089;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            cursor: pointer;
            font-size: 28px;
            font-weight: 400;
            line-height: 1;
            transition: background 180ms ease, border-color 180ms ease, color 180ms ease;
        }

        .history-close:hover {
            color: #ec0a78;
            background: #fff0f8;
            border-color: #ffc7e3;
        }

        .history-dialog-controls {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            padding: 16px 24px 8px;
        }

        .history-search {
            display: flex;
            min-height: 44px;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
        }

        .history-search span {
            color: #8aa0ba;
            font-size: 20px;
            font-weight: 400;
        }

        .history-search input {
            width: 100%;
            min-width: 0;
            color: #172033;
            background: transparent;
            border: 0;
            outline: 0;
            font: inherit;
            font-size: 14px;
            font-weight: 400;
        }

        .history-sort {
            min-height: 44px;
            padding: 0 18px;
            color: #23324a;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .history-sort:hover {
            color: #ec0a78;
            border-color: #ff9dcc;
        }

        .history-count-copy {
            margin: 0;
            padding: 0 24px 14px;
            color: #7b8da7;
            font-size: 12px;
            font-weight: 500;
        }

        .history-dialog-body {
            min-height: 0;
            overflow-y: auto;
            padding: 0 24px 24px;
        }

        .history-table-scroll {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e7edf5;
            border-radius: 10px;
        }

        .history-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 14px 16px;
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
        }

        .history-table th {
            color: #8da0b9;
            background: #f8fafc;
            font-weight: 500;
            text-transform: uppercase;
        }

        .history-table td {
            color: #23324a;
            background: #ffffff;
            border-top: 1px solid #eef2f7;
            font-weight: 400;
        }

        .history-table .is-main {
            color: #061125;
            font-weight: 500;
        }

        .history-table .is-red {
            color: #dc2626;
            font-weight: 500;
        }

        .history-table .is-blue {
            color: #2563eb;
            font-weight: 500;
        }

        .history-status {
            display: inline-flex;
            min-height: 24px;
            align-items: center;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .history-status.is-good {
            color: #008a61;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
        }

        .history-status.is-warning {
            color: #a16207;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        .history-status.is-critical {
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .history-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .history-row-actions button {
            min-height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }

        .history-row-actions .is-edit {
            color: #ec0a78;
            background: #fff3fa;
            border: 1px solid #ffd4e7;
        }

        .history-row-actions .is-delete {
            color: #b91c1c;
            background: #fff7f7;
            border: 1px solid #fecaca;
        }

        .history-row-actions button:hover {
            transform: translateY(-1px);
        }

        .history-empty {
            margin: 0;
            padding: 34px 18px;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            font-weight: 400;
        }

        .casefile-journey-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
            margin-top: 18px;
        }

        .casefile-journey-grid article {
            position: relative;
            min-height: 112px;
            padding: 34px 14px 14px;
            color: #8da0b9;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
        }

        .casefile-journey-grid article::before {
            position: absolute;
            top: 16px;
            left: 14px;
            width: 11px;
            height: 11px;
            content: "";
            background: #cbd5e1;
            border-radius: 999px;
        }

        .casefile-journey-grid article.is-complete {
            color: #008a61;
            background: #ecfdf5;
            border-color: #8cecc0;
        }

        .casefile-journey-grid article.is-complete::before {
            background: #008a61;
        }

        .casefile-journey-grid article.is-current {
            color: #d80b78;
            background: #fff4fa;
            border-color: #ffb8db;
            box-shadow: inset 0 0 0 3px #ffe5f1;
        }

        .casefile-journey-grid article.is-current::before {
            background: #d80b78;
        }

        .casefile-journey-grid strong,
        .casefile-journey-grid span,
        .casefile-journey-grid small {
            display: block;
        }

        .casefile-journey-grid strong {
            color: currentColor;
            font-size: 14px;
            font-weight: 900;
        }

        .casefile-journey-grid span {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .casefile-journey-grid small {
            margin-top: 14px;
            color: currentColor;
            font-size: 11px;
            font-weight: 900;
        }

        .casefile-activity-list {
            display: grid;
            gap: 10px;
            max-height: 360px;
            overflow-y: auto;
            margin-top: 18px;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
        }

        .casefile-activity-list article,
        .casefile-record-row {
            padding: 14px 18px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
        }

        .casefile-activity-list strong,
        .casefile-record-row strong {
            color: #061125;
            font-size: 14px;
            font-weight: 900;
        }

        .casefile-activity-list strong span {
            margin-left: 8px;
            padding: 4px 8px;
            color: #607089;
            background: #eef2f7;
            border-radius: 6px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .casefile-activity-list p,
        .casefile-activity-list time,
        .casefile-record-row span {
            display: block;
            margin: 6px 0 0;
            color: #53647c;
            font-size: 12px;
            font-weight: 700;
        }

        .casefile-record-row {
            display: grid;
            grid-template-columns: 1.2fr repeat(3, minmax(0, 1fr)) auto;
            gap: 12px;
            align-items: center;
            margin-top: 12px;
        }

        .casefile-record-row button {
            min-height: 36px;
            color: #ec0a78;
            background: #fff3fa;
            border: 1px solid #ffd4e7;
            border-radius: 8px;
            padding: 0 14px;
            font-size: 12px;
            font-weight: 600;
        }

        .casefile-record-row button:hover {
            color: #ffffff;
            background: #ec0a78;
            border-color: #ec0a78;
        }

        .vitals-success {
            position: fixed;
            right: 28px;
            bottom: 28px;
            z-index: 95;
            padding: 14px 18px;
            color: #008a61;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
            border-radius: 10px;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
            font-size: 14px;
            font-weight: 600;
        }

        .vitals-success[hidden],
        .vitals-modal[hidden] {
            display: none;
        }

        .vitals-modal {
            position: fixed;
            inset: 0;
            z-index: 92;
            display: grid;
            place-items: center;
            padding: 28px;
        }

        .vitals-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.68);
        }

        .vitals-dialog {
            position: relative;
            z-index: 1;
            display: grid;
            width: min(680px, calc(100vw - 32px));
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 28px 80px rgba(3, 7, 18, 0.34);
        }

        .vitals-dialog-header {
            position: sticky;
            top: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 22px;
            background: #ffffff;
            border-bottom: 1px solid #e3eaf3;
        }

        .vitals-dialog-header h2 {
            margin: 0;
            color: #061125;
            font-size: 21px;
            font-weight: 700;
        }

        .vitals-dialog-header button {
            width: 38px;
            height: 38px;
            color: #53647c;
            background: #ffffff;
            border: 0;
            border-radius: 999px;
            padding: 0;
            font-size: 26px;
            font-weight: 400;
        }

        .vitals-dialog-header button:hover {
            color: #ec0a78;
            background: #fff3fa;
        }

        .vitals-warning {
            display: flex;
            gap: 12px;
            margin: 20px 22px 0;
            padding: 14px 16px;
            color: #9a3412;
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 8px;
        }

        .vitals-warning strong {
            display: block;
            margin-bottom: 8px;
            color: #7c2d12;
            font-size: 14px;
            font-weight: 700;
        }

        .vitals-warning ul {
            display: grid;
            gap: 5px;
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
            font-weight: 400;
        }

        .vitals-warning p {
            margin: 10px 0 0;
            font-size: 12px;
            font-weight: 500;
        }

        .vitals-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 18px 22px 22px;
        }

        .vitals-field-grid label {
            display: grid;
            gap: 7px;
            margin: 0;
        }

        .vitals-field-grid label.is-wide {
            grid-column: 1 / -1;
        }

        .vitals-field-grid span {
            color: #53647c;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .vitals-field-grid input,
        .vitals-field-grid textarea {
            width: 100%;
            min-height: 40px;
            color: #10213f;
            background: #ffffff;
            border: 1px solid #cdd9e8;
            border-radius: 8px;
            padding: 10px 12px;
            font: inherit;
            font-size: 14px;
            font-weight: 400;
            outline: 0;
        }

        .vitals-field-grid textarea {
            resize: vertical;
        }

        .vitals-field-grid input:focus,
        .vitals-field-grid textarea:focus {
            border-color: #ec0a78;
            box-shadow: 0 0 0 3px rgba(236, 10, 120, 0.12);
        }

        .vitals-field-grid label.has-error input,
        .vitals-field-grid label.has-error textarea {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.08);
        }

        .vitals-field-grid small {
            min-height: 16px;
            color: #dc2626;
            font-size: 12px;
            font-weight: 400;
        }

        .vitals-dialog-footer {
            position: sticky;
            bottom: 0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 22px;
            background: #ffffff;
            border-top: 1px solid #e3eaf3;
        }

        .vitals-cancel,
        .vitals-save {
            min-height: 42px;
            border-radius: 8px;
            padding: 0 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .vitals-cancel {
            color: #324663;
            background: #ffffff;
            border: 1px solid #cdd9e8;
        }

        .vitals-save {
            gap: 9px;
            color: #ffffff;
            background: #ec0a78;
            border: 1px solid #ec0a78;
        }

        .vitals-save:disabled {
            cursor: wait;
            opacity: 0.75;
        }

        .vitals-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #ffffff;
            border-radius: 999px;
            animation: vitals-spin 800ms linear infinite;
        }

        @keyframes vitals-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .maternal-monitoring-shell {
            display: grid;
            gap: 24px;
            width: min(1420px, 100%);
            margin: 0 auto;
            color: #10213f;
        }

        .maternal-monitoring-shell svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .maternal-monitoring-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #cad6e5;
        }

        .maternal-monitoring-heading h1,
        .maternal-monitoring-heading p,
        .maternal-panel h2,
        .maternal-section-heading h2,
        .maternal-chart-card h3 {
            margin: 0;
        }

        .maternal-monitoring-heading h1 {
            color: #030813;
            font-size: 25px;
            font-weight: 900;
            line-height: 1.15;
        }

        .maternal-monitoring-heading p,
        .maternal-section-heading p,
        .maternal-panel-title p {
            color: #40536f;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
        }

        .maternal-heading-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .maternal-chip,
        .maternal-risk-badge,
        .maternal-mini-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 30px;
            padding: 0 14px;
            color: #53647c;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .maternal-chip.is-pink {
            color: #ec0a78;
            background: #fff8fc;
            border-color: #ffd1e7;
        }

        .maternal-panel,
        .maternal-chart-card,
        .maternal-progress-card,
        .maternal-history-link {
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 10px;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
        }

        .maternal-vitals-panel,
        .maternal-panel {
            padding: 18px;
        }

        .maternal-panel-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid #dbe5f0;
        }

        .maternal-panel-title h2,
        .maternal-panel h2,
        .maternal-section-heading h2 {
            color: #030813;
            font-size: 18px;
            font-weight: 900;
        }

        .maternal-risk-badge,
        .maternal-mini-badge.is-good,
        .maternal-risk-badge.is-low {
            color: #008a61;
            background: #ecfdf5;
            border-color: #90f0c5;
        }

        .maternal-mini-badge.is-warning,
        .maternal-risk-badge.is-medium {
            color: #b45309;
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .maternal-mini-badge.is-neutral {
            color: #64748b;
            background: #f8fafc;
            border-color: #dbe5f0;
        }

        .maternal-risk-badge.is-high {
            color: #dc2626;
            background: #fff1f2;
            border-color: #fecaca;
        }

        .maternal-vitals-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            padding-top: 16px;
        }

        .maternal-vital-card,
        .maternal-stat-card,
        .maternal-guidelines-grid article {
            border: 1px solid #dbe5f0;
            border-radius: 9px;
            background: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
        }

        .maternal-vital-card {
            display: grid;
            min-height: 154px;
            padding: 16px;
        }

        .maternal-vital-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .maternal-vital-icon {
            display: inline-grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 8px;
        }

        .maternal-vital-icon.is-pink {
            color: #ec0a78;
            background: #fff0f8;
            border: 1px solid #ffd4e7;
        }

        .maternal-vital-icon.is-green {
            color: #0f9f6e;
            background: #ecfdf5;
            border: 1px solid #baf0d7;
        }

        .maternal-vital-icon.is-blue {
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .maternal-vital-card p,
        .maternal-vital-card div > span,
        .maternal-stat-card span,
        .maternal-guidelines-grid span,
        .maternal-chart-card > span,
        .maternal-risk-box span,
        .maternal-section-heading span {
            color: #8191aa;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .maternal-vital-card strong {
            margin-top: 24px;
            color: #030813;
            font-size: 30px;
            font-weight: 600;
            line-height: 1;
        }

        .maternal-vital-card strong span {
            color: #40536f;
            font-size: 13px;
            font-weight: 400;
        }

        .maternal-vital-card > div:last-child {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid #e8eef5;
        }

        .maternal-vital-card b {
            color: #061125;
            font-size: 11px;
            font-weight: 900;
            text-align: right;
        }

        .maternal-good-banner,
        .maternal-warning-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 900;
        }

        .maternal-good-banner {
            color: #007f59;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
        }

        .maternal-warning-banner {
            color: #9a3412;
            background: #fff8e6;
            border: 1px solid #fbbf24;
        }

        .maternal-dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 24px;
        }

        .maternal-column {
            display: grid;
            align-content: start;
            gap: 14px;
        }

        .maternal-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
        }

        .maternal-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .maternal-stat-grid.is-three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .maternal-stat-card {
            min-height: 94px;
            padding: 16px;
        }

        .maternal-stat-card strong {
            display: block;
            margin-top: 10px;
            color: #061125;
            font-size: 23px;
            font-weight: 600;
            line-height: 1.1;
        }

        .maternal-stat-card p {
            margin: 8px 0 0;
            color: #40536f;
            font-size: 11px;
            font-weight: 700;
        }

        .maternal-stat-card.is-pink {
            background: #fff4fa;
            border-color: #ffd4e7;
        }

        .maternal-stat-card.is-pink strong {
            color: #d80b78;
        }

        .maternal-stat-card.is-green {
            background: #ecfdf5;
            border-color: #baf0d7;
        }

        .maternal-stat-card.is-green strong {
            color: #008a61;
        }

        .maternal-stat-card.is-blue {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .maternal-stat-card.is-blue strong {
            color: #1d4ed8;
        }

        .maternal-stat-card.is-red {
            background: #fff1f2;
            border-color: #fecaca;
        }

        .maternal-stat-card.is-red strong {
            color: #dc2626;
        }

        .maternal-progress-card {
            padding: 16px;
        }

        .maternal-progress-card > div:first-child,
        .maternal-progress-labels {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #40536f;
            font-size: 11px;
            font-weight: 900;
        }

        .maternal-progress-card b {
            color: #008a61;
            background: #ecfdf5;
            border: 1px solid #8cecc0;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 10px;
        }

        .maternal-progress-track {
            height: 12px;
            margin: 14px 0 9px;
            overflow: hidden;
            background: #eef3f8;
            border-radius: 999px;
        }

        .maternal-progress-track span {
            display: block;
            height: 100%;
            background: #0f9f6e;
            border-radius: inherit;
        }

        .maternal-chart-card {
            position: relative;
            padding: 22px;
        }

        .maternal-chart-card h3 {
            margin-top: 6px;
            color: #030813;
            font-size: 17px;
            font-weight: 600;
        }

        .maternal-chart-card i {
            position: absolute;
            top: 22px;
            right: 22px;
            color: #ec0a78;
            font-style: normal;
        }

        .maternal-chart-area {
            position: relative;
            display: grid;
            min-height: 242px;
            margin-top: 18px;
            place-items: center;
            overflow: hidden;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e8eef5;
            border-radius: 8px;
        }

        .maternal-chart-area::before {
            display: none;
            content: "";
        }

        .maternal-chart-area::after {
            display: none;
            content: "";
        }

        .maternal-chart-dot {
            position: absolute;
            z-index: 1;
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .maternal-chart-dot.is-pink,
        .maternal-chart-legend .is-pink {
            background: #dc2d82;
        }

        .maternal-chart-dot.is-red,
        .maternal-chart-legend .is-red {
            background: #dc2626;
        }

        .maternal-chart-dot.is-blue,
        .maternal-chart-legend .is-blue {
            background: #2563eb;
        }

        .maternal-chart-area svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .maternal-chart-area svg .axis {
            stroke: #cbd5e1;
            stroke-width: 1.4;
        }

        .maternal-chart-area svg .grid {
            stroke: #dce5f0;
            stroke-dasharray: 5 8;
            stroke-width: 1;
        }

        .maternal-chart-area svg text {
            fill: #8090a9;
            stroke: none !important;
            stroke-width: 0 !important;
            paint-order: fill;
            font-size: 10px;
            font-weight: 400;
            text-shadow: none !important;
        }

        .maternal-chart-area svg text.empty {
            fill: #64748b;
            font-size: 12px;
            font-weight: 400;
        }

        .maternal-chart-area .weight-line,
        .maternal-chart-area .systolic-line,
        .maternal-chart-area .diastolic-line {
            fill: none;
            stroke-width: 2;
        }

        .maternal-chart-area .weight-line {
            stroke: #dc2d82;
        }

        .maternal-chart-area .systolic-line {
            stroke: #dc2626;
        }

        .maternal-chart-area .diastolic-line {
            stroke: #2563eb;
        }

        .maternal-chart-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #40536f;
            font-size: 11px;
            font-weight: 500;
        }

        .maternal-chart-legend span {
            width: 11px;
            height: 11px;
            border-radius: 999px;
        }

        .maternal-history-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 18px;
            color: #ec0a78;
            font-size: 13px;
            font-weight: 600;
        }

        .maternal-history-link:hover {
            color: #d80b78;
            text-decoration: none;
        }

        .maternal-history-link b {
            color: #40536f;
            background: #f8fafc;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .maternal-history-list {
            display: grid;
            gap: 8px;
        }

        .maternal-history-list:empty {
            display: none;
        }

        .maternal-history-list article {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
            padding: 12px 14px;
            color: #40536f;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 400;
        }

        .maternal-history-list strong {
            color: #061125;
            font-weight: 600;
        }

        .maternal-history-list b {
            color: #ec0a78;
            font-weight: 600;
        }

        .maternal-bottom-grid {
            display: grid;
            grid-template-columns: minmax(260px, 0.35fr) minmax(0, 1fr);
            gap: 20px;
        }

        .maternal-risk-box {
            margin-top: 18px;
            padding: 18px;
            border-radius: 8px;
            border: 1px solid #baf0d7;
            background: #ecfdf5;
        }

        .maternal-risk-box.is-medium {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        .maternal-risk-box.is-high {
            border-color: #fecaca;
            background: #fff1f2;
        }

        .maternal-risk-box strong {
            display: block;
            margin: 8px 0 12px;
            color: #008a61;
            font-size: 30px;
            font-weight: 900;
        }

        .maternal-risk-box.is-medium strong {
            color: #b45309;
        }

        .maternal-risk-box.is-high strong {
            color: #dc2626;
        }

        .maternal-risk-box p {
            margin: 0;
            color: #007f59;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.6;
        }

        .maternal-risk-box.is-medium p {
            color: #7c2d12;
        }

        .maternal-risk-box.is-high p {
            color: #991b1b;
        }

        .maternal-guidelines-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .maternal-guidelines-grid article {
            min-height: 108px;
            padding: 16px;
            background: #f8fafc;
        }

        .maternal-guidelines-grid p {
            margin: 10px 0 0;
            color: #1f334f;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.45;
        }

        .kaalaman-shell {
            display: grid;
            gap: 18px;
            width: min(980px, 100%);
            margin: 0 auto;
            color: #0f1f35;
        }

        .kaalaman-shell svg {
            width: 18px;
            height: 18px;
            max-width: 18px;
            max-height: 18px;
            flex: 0 0 auto;
            display: inline-block;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .kaalaman-shell button {
            font-family: inherit;
            line-height: 1.2;
        }

        .kaalaman-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(170px, 0.24fr);
            gap: 18px;
            align-items: center;
            padding: 26px;
            color: #ffffff;
            background: #07091a;
            border: 1px solid #1d2340;
            border-radius: 12px;
            box-shadow: 0 14px 28px rgba(7, 9, 26, 0.18);
        }

        .kaalaman-hero h1,
        .kaalaman-hero p {
            margin: 0;
        }

        .kaalaman-hero h1 {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            font-size: 22px;
            font-weight: 900;
        }

        .kaalaman-hero p {
            margin-top: 12px;
            max-width: 720px;
            color: #eef2ff;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.6;
        }

        .kaalaman-hero svg,
        .kaalaman-meta-icon svg,
        .kaalaman-section-title svg,
        .kaalaman-card-icon svg,
        .kaalaman-video-play svg,
        .kaalaman-status-icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .kaalaman-focus-card {
            min-height: 78px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 9px;
        }

        .kaalaman-eyebrow {
            display: block;
            margin-bottom: 7px;
            color: #d8dff7;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .kaalaman-focus-card strong {
            display: block;
            font-size: 16px;
            font-weight: 900;
        }

        .kaalaman-focus-card small {
            display: block;
            margin-top: 8px;
            color: #ffffff;
            font-size: 11px;
            font-weight: 900;
        }

        .kaalaman-trimester-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .kaalaman-trimester-card {
            appearance: none;
            display: block;
            width: 100%;
            min-height: 100%;
            padding: 18px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #dde6f0;
            border-radius: 12px;
            color: inherit;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
            transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
            font: inherit;
        }

        .kaalaman-trimester-card.is-current,
        .kaalaman-trimester-card.is-selected {
            border-color: #ff8fc9;
            box-shadow: inset 0 0 0 1px #ffb6d9, 0 10px 24px rgba(236, 10, 120, 0.10);
        }

        .kaalaman-trimester-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(15, 23, 42, 0.10);
        }

        .kaalaman-trimester-card:active {
            transform: translateY(0) scale(0.985);
        }

        .kaalaman-trimester-card:focus-visible {
            outline: 3px solid rgba(236, 10, 120, 0.18);
            outline-offset: 3px;
        }

        .kaalaman-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            padding: 0 14px;
            color: #d80b78;
            background: #ffe7f3;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
        }

        .kaalaman-pill.is-muted {
            color: #64748b;
            background: #f1f5f9;
        }

        .kaalaman-trimester-card h2,
        .kaalaman-trimester-title {
            display: block;
            margin: 12px 0 4px;
            color: #030813;
            font-size: 16px;
            font-weight: 900;
        }

        .kaalaman-trimester-card p,
        .kaalaman-trimester-subtitle {
            display: block;
            margin: 0 0 14px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .kaalaman-metric-list {
            display: grid;
            gap: 9px;
            width: 100%;
            margin-top: 14px;
        }

        .kaalaman-metric {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            min-height: 32px;
            padding: 0 10px;
            color: #334155;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
        }

        .kaalaman-metric span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .kaalaman-metric strong {
            color: #030813;
            font-size: 12px;
            font-weight: 900;
        }

        .kaalaman-months {
            display: grid;
            gap: 12px;
        }

        .kaalaman-month {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #dde6f0;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .kaalaman-month.is-hidden {
            display: none;
        }

        .kaalaman-month[open] {
            border-color: #ec0a78;
            box-shadow: 0 12px 28px rgba(236, 10, 120, 0.12);
        }

        .kaalaman-month-summary {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 16px 18px;
            cursor: pointer;
            list-style: none;
            transition: background 180ms ease;
        }

        .kaalaman-month-summary::-webkit-details-marker {
            display: none;
        }

        .kaalaman-month-summary:hover {
            background: #fff7fb;
        }

        .kaalaman-month-summary:active {
            background: #ffeaf5;
        }

        .kaalaman-month-summary:focus-visible,
        .kaalaman-button:focus-visible,
        .kaalaman-video-action:focus-visible {
            outline: 3px solid rgba(236, 10, 120, 0.18);
            outline-offset: 3px;
        }

        .kaalaman-month-badge {
            display: inline-flex;
            min-width: 68px;
            min-height: 28px;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: #ec0a78;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .kaalaman-month:not([open]) .kaalaman-month-badge {
            color: #1e293b;
            background: #f1f5f9;
        }

        .kaalaman-month-title {
            margin: 0;
            color: #030813;
            font-size: 16px;
            font-weight: 900;
        }

        .kaalaman-month-title span {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .kaalaman-month-hint {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .kaalaman-chevron {
            color: #64748b;
            transition: transform 180ms ease;
        }

        .kaalaman-chevron svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .kaalaman-month[open] .kaalaman-chevron {
            transform: rotate(180deg);
        }

        .kaalaman-month-body {
            display: grid;
            gap: 18px;
            padding: 6px 22px 22px;
        }

        .kaalaman-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .kaalaman-info-card {
            padding: 18px;
            background: #ffffff;
            border: 1px solid #dde6f0;
            border-radius: 10px;
        }

        .kaalaman-info-card h3 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            color: #d80b78;
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .kaalaman-info-card.is-blue h3 {
            color: #2046d8;
        }

        .kaalaman-info-card p {
            margin: 0;
            color: #26364d;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.6;
        }

        .kaalaman-read-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px;
            background: #fff8fc;
            border: 1px solid #f6dbe9;
            border-radius: 10px;
        }

        .kaalaman-read-panel > div {
            min-width: 0;
        }

        .kaalaman-read-panel h3 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: #030813;
            font-size: 13px;
            font-weight: 900;
        }

        .kaalaman-read-panel p {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .kaalaman-button,
        .kaalaman-video-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            box-shadow: none;
            transition: transform 180ms ease, background 180ms ease, box-shadow 180ms ease;
        }

        .kaalaman-button {
            min-height: 38px;
            padding: 0 18px;
            color: #ffffff;
            background: #07091a;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .kaalaman-button:hover {
            background: #ec0a78;
            box-shadow: 0 10px 18px rgba(236, 10, 120, 0.20);
            text-decoration: none;
        }

        .kaalaman-button.secondary {
            color: #334155;
            background: #f1f5f9;
        }

        .kaalaman-button.secondary:hover {
            color: #ffffff;
            background: #334155;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.14);
        }

        .kaalaman-button:active,
        .kaalaman-video-action:active {
            transform: scale(0.975);
        }

        .kaalaman-button.is-done,
        .kaalaman-video-action.is-done {
            background: #0f9f6e;
            color: #ffffff;
        }

        .kaalaman-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: #030813;
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .kaalaman-video-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .kaalaman-video-card {
            display: grid;
            min-height: 126px;
            padding: 14px;
            background: #ffffff;
            border: 1px solid #dde6f0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
        }

        .kaalaman-video-card.is-clickable {
            cursor: pointer;
            transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
        }

        .kaalaman-video-card.is-clickable:hover {
            border-color: #ffabd3;
            box-shadow: 0 14px 26px rgba(236, 10, 120, 0.10);
            transform: translateY(-2px);
        }

        .kaalaman-video-card.is-clickable:active {
            transform: translateY(0) scale(0.99);
        }

        .kaalaman-video-card.is-clickable:focus-visible {
            outline: 3px solid rgba(236, 10, 120, 0.18);
            outline-offset: 3px;
        }

        .kaalaman-video-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .kaalaman-video-play {
            display: inline-grid;
            width: 30px;
            height: 30px;
            place-items: center;
            color: #ec0a78;
            background: #ffe7f3;
            border-radius: 999px;
        }

        .kaalaman-video-action {
            min-height: 24px;
            padding: 0 10px;
            color: #111827;
            background: #f1f5f9;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
        }

        .kaalaman-video-card h3 {
            margin: 16px 0 12px;
            color: #030813;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.3;
        }

        .kaalaman-video-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: auto;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
        }

        .kaalaman-video-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .kaalaman-video-meta svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .kaalaman-video-meta strong {
            color: #d80b78;
            text-transform: uppercase;
        }

        .kaalaman-more {
            justify-self: center;
            min-width: 140px;
            color: #ffffff;
            background: #ec0a78;
            border-radius: 999px;
        }

        .kaalaman-status-panel {
            padding: 16px;
            background: #ffffff;
            border: 1px solid #dde6f0;
            border-radius: 12px;
        }

        .kaalaman-status-panel p {
            margin: 5px 0 14px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .kaalaman-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .kaalaman-status-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 9px;
            align-items: center;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e6edf5;
            border-radius: 10px;
        }

        .kaalaman-status-mini-icon {
            display: inline-grid;
            width: 28px;
            height: 28px;
            place-items: center;
            color: #ec0a78;
            background: #fff0f8;
            border-radius: 8px;
        }

        .kaalaman-status-mini-icon.is-blue {
            color: #2563eb;
            background: #eff6ff;
        }

        .kaalaman-status-mini-icon.is-warning {
            color: #c2410c;
            background: #fff7ed;
        }

        .kaalaman-status-mini-icon.is-success {
            color: #008a61;
            background: #ecfdf5;
        }

        .kaalaman-status-card.is-needed {
            background: #fffdf0;
            border-color: #f6e2a5;
        }

        .kaalaman-status-card.is-complete {
            background: #ecfdf5;
            border-color: #baf0d7;
        }

        .kaalaman-status-card > div > span {
            display: block;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .kaalaman-status-card > div > strong {
            display: block;
            margin-top: 6px;
            color: #030813;
            font-size: 13px;
            font-weight: 900;
        }

        .kaalaman-status-card.is-needed > div > strong {
            color: #c2410c;
        }

        .kaalaman-status-card.is-complete > div > strong {
            color: #008a61;
        }

        .kaalaman-divider {
            height: 1px;
            background: #e8eef5;
        }

        .kaalaman-risk-panel,
        .kaalaman-note-panel,
        .kaalaman-complete-panel {
            padding: 16px;
            border-radius: 10px;
        }

        .kaalaman-risk-panel {
            background: #fff7f7;
            border: 1px solid #fecaca;
        }

        .kaalaman-risk-panel h3,
        .kaalaman-task-title,
        .kaalaman-upload-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            color: #030813;
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .kaalaman-risk-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 12px;
            align-items: flex-start;
            padding: 14px;
            background: #fffafa;
            border: 1px solid #fecaca;
            border-radius: 10px;
        }

        .kaalaman-risk-icon,
        .kaalaman-task-icon,
        .kaalaman-infographic-icon,
        .kaalaman-complete-icon {
            display: inline-grid;
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 9px;
        }

        .kaalaman-risk-icon {
            color: #dc2626;
            background: #fee2e2;
        }

        .kaalaman-risk-icon svg,
        .kaalaman-task-icon svg,
        .kaalaman-infographic-icon svg,
        .kaalaman-complete-icon svg,
        .kaalaman-upload-title svg,
        .kaalaman-risk-panel h3 svg,
        .kaalaman-task-title svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .kaalaman-risk-name {
            display: block;
            margin-bottom: 6px;
            color: #dc2626;
            font-size: 14px;
            font-weight: 900;
        }

        .kaalaman-risk-copy {
            margin: 0;
            color: #24344d;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.6;
        }

        .kaalaman-lower-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 16px;
        }

        .kaalaman-task-list {
            display: grid;
            gap: 12px;
        }

        .kaalaman-task-card,
        .kaalaman-infographic-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 14px;
            background: #f8fafc;
            border: 1px solid #dde6f0;
            border-radius: 10px;
        }

        .kaalaman-infographic-card.is-clickable {
            cursor: pointer;
            transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease, background 180ms ease;
        }

        .kaalaman-infographic-card.is-clickable:hover {
            background: #fff8fc;
            border-color: #ffabd3;
            box-shadow: 0 12px 22px rgba(236, 10, 120, 0.10);
            transform: translateY(-2px);
        }

        .kaalaman-infographic-card.is-clickable:active {
            transform: translateY(0) scale(0.99);
        }

        .kaalaman-infographic-card.is-clickable:focus-visible {
            outline: 3px solid rgba(236, 10, 120, 0.18);
            outline-offset: 3px;
        }

        .kaalaman-task-icon {
            color: #64748b;
            background: #ffffff;
            border: 1px solid #d7e2ee;
        }

        .kaalaman-infographic-icon {
            color: #ec0a78;
            background: #fff0f8;
        }

        .kaalaman-task-card h4,
        .kaalaman-infographic-card h4 {
            margin: 0 0 5px;
            color: #030813;
            font-size: 13px;
            font-weight: 900;
        }

        .kaalaman-task-card p,
        .kaalaman-infographic-card p {
            margin: 0;
            color: #53647c;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.45;
        }

        .kaalaman-task-card strong,
        .kaalaman-infographic-card strong {
            color: #ec0a78;
            font-size: 11px;
            font-weight: 900;
        }

        .kaalaman-review-link {
            color: #ec0a78;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .kaalaman-upload-panel {
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #dde6f0;
            border-radius: 10px;
        }

        .kaalaman-upload-row {
            display: grid;
            grid-template-columns: minmax(170px, 0.8fr) minmax(240px, 1.1fr) minmax(250px, 0.85fr);
            gap: 14px;
            align-items: end;
        }

        .kaalaman-upload-field label {
            display: block;
            margin-bottom: 7px;
            color: #53647c;
            font-size: 11px;
            font-weight: 900;
        }

        .kaalaman-upload-field select,
        .kaalaman-upload-field input[type="file"] {
            width: 100%;
            min-height: 42px;
            padding: 0 14px;
            background: #ffffff;
            border: 1px solid #d7e2ee;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 800;
        }

        .kaalaman-file-picker {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            width: 100%;
            min-height: 42px;
            margin: 0;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #d7e2ee;
            border-radius: 10px;
            cursor: pointer;
        }

        .kaalaman-file-picker input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .kaalaman-file-picker span {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            padding: 0 14px;
            color: #ec0a78;
            background: #fff0f8;
            font-size: 12px;
            font-weight: 900;
        }

        .kaalaman-file-picker strong {
            overflow: hidden;
            padding: 0 14px;
            color: #10213f;
            font-size: 13px;
            font-weight: 900;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .kaalaman-upload-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: end;
        }

        .kaalaman-upload-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            color: #ffffff;
            background: #ec0a78;
            border-radius: 10px;
            padding: 0 18px;
        }

        .kaalaman-upload-cancel {
            min-height: 42px;
        }

        .kaalaman-note-panel {
            color: #843b00;
            background: #fff8e6;
            border: 1px solid #fbbf24;
        }

        .kaalaman-note-panel h3 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 9px;
            color: #b45309;
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .kaalaman-note-panel p {
            margin: 0;
            color: #3f2a0b;
            font-size: 13px;
            font-style: italic;
            font-weight: 700;
            line-height: 1.6;
        }

        .kaalaman-complete-panel {
            display: flex;
            gap: 12px;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #dde6f0;
        }

        .kaalaman-complete-icon {
            color: #8aa0bc;
            background: #ffffff;
            border: 1px solid #d7e2ee;
        }

        .kaalaman-complete-panel h3 {
            margin: 0 0 4px;
            color: #030813;
            font-size: 14px;
            font-weight: 900;
        }

        .kaalaman-complete-panel p {
            margin: 0;
            color: #53647c;
            font-size: 12px;
            font-weight: 700;
        }

        .kaalaman-library-shell {
            display: grid;
            gap: 28px;
            width: min(1180px, 100%);
            margin: 0 auto;
            color: #10213f;
        }

        .kaalaman-library-back {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            color: #40536f;
            font-size: 14px;
            font-weight: 900;
        }

        .kaalaman-library-back:hover {
            color: #ec0a78;
            text-decoration: none;
        }

        .kaalaman-library-heading {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            padding-bottom: 26px;
            border-bottom: 1px solid #dbe5f0;
        }

        .kaalaman-library-icon {
            display: inline-grid;
            width: 58px;
            height: 58px;
            flex: 0 0 auto;
            place-items: center;
            color: #ec0a78;
            background: #fff0f8;
            border-radius: 14px;
        }

        .kaalaman-library-icon svg,
        .kaalaman-library-back svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .kaalaman-library-kicker {
            margin: 0 0 8px;
            color: #ec0a78;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .kaalaman-library-heading h1 {
            margin: 0;
            color: #030813;
            font-size: 32px;
            font-weight: 900;
            line-height: 1.15;
        }

        .kaalaman-library-heading p {
            margin: 12px 0 0;
            color: #334155;
            font-size: 16px;
            font-weight: 700;
        }

        .kaalaman-library-section {
            display: grid;
            gap: 18px;
        }

        .kaalaman-library-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .kaalaman-library-card {
            min-height: 180px;
            padding: 24px;
        }

        .kaalaman-library-card h3 {
            margin-top: 34px;
            font-size: 18px;
        }

        .kaalaman-archive-card {
            border-style: dashed;
            background: #fffafe;
        }

        body.has-kaalaman-modal {
            overflow: hidden;
        }

        .kaalaman-video-modal[hidden] {
            display: none;
        }

        .kaalaman-video-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .kaalaman-video-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(3, 8, 19, 0.58);
        }

        .kaalaman-video-dialog {
            position: relative;
            z-index: 1;
            width: min(520px, 100%);
            padding: 28px;
            background: #ffffff;
            border: 1px solid #dbe5f0;
            border-radius: 16px;
            box-shadow: 0 24px 70px rgba(3, 8, 19, 0.30);
        }

        .kaalaman-video-dialog h2 {
            margin: 6px 0 8px;
            color: #030813;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.2;
        }

        .kaalaman-video-dialog p {
            margin: 0;
            color: #53647c;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.55;
        }

        .kaalaman-video-modal-copy {
            margin-top: 14px !important;
        }

        .kaalaman-video-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 38px;
            height: 38px;
            padding: 0;
            color: #53647c;
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            font-size: 22px;
            line-height: 1;
        }

        .kaalaman-video-close:hover {
            color: #ffffff;
            background: #ec0a78;
        }

        .kaalaman-video-play.is-large {
            width: 48px;
            height: 48px;
            margin-bottom: 14px;
        }

        .kaalaman-infographic-icon.is-large {
            display: inline-grid;
            width: 48px;
            height: 48px;
            place-items: center;
            color: #ec0a78;
            background: #fff0f8;
            border-radius: 12px;
            margin-bottom: 14px;
        }

        .kaalaman-modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .portal-overlay {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: none;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
        }

        body.auth-body {
            color: var(--inay-text);
            background: #fbfbfc;
            border-top: 4px solid #2e3442;
        }

        .auth-page {
            width: min(420px, calc(100% - 32px));
            margin: 0 auto;
            padding: 0 0 36px;
        }

        .auth-header {
            display: grid;
            justify-items: center;
            gap: 8px;
            padding: 0 0 28px;
            text-align: center;
        }

        .auth-mark {
            display: inline-grid;
            width: 54px;
            height: 54px;
            margin-top: -2px;
            place-items: center;
            color: #ff3a99;
            background: var(--inay-navy);
            border-radius: 999px;
            box-shadow: 0 8px 14px rgba(16, 26, 50, 0.24);
        }

        .auth-mark svg {
            width: 26px;
            height: 26px;
            fill: currentColor;
        }

        .auth-title {
            margin: 12px 0 0;
            color: #030813;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: 0;
        }

        .auth-kicker {
            color: var(--inay-pink);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .auth-subtitle {
            width: min(420px, 100%);
            margin: 8px 0 0;
            color: #46638a;
            font-size: 12px;
            line-height: 1.55;
        }

        .auth-card {
            overflow: hidden;
            padding: 30px;
            background: #ffffff;
            border: 1px solid var(--inay-line);
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(17, 31, 56, 0.14);
        }

        .auth-card-title {
            margin: 0;
            color: #030813;
            font-size: 15px;
            font-weight: 900;
            text-align: center;
        }

        .auth-card-subtitle {
            margin: 8px 0 24px;
            color: var(--inay-muted);
            font-size: 12px;
            line-height: 1.4;
            text-align: center;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .role-option {
            display: grid;
            min-height: 74px;
            place-items: center;
            gap: 8px;
            padding: 14px 10px;
            color: #536f91;
            background: #ffffff;
            border: 1px solid var(--inay-line);
            border-radius: 14px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
        }

        .role-option:hover {
            text-decoration: none;
            border-color: #ff6ab0;
        }

        .role-option.is-active {
            color: var(--inay-pink);
            background: var(--inay-soft-pink);
            border-color: #ff2d91;
        }

        .role-option svg {
            width: 21px;
            height: 21px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .auth-field {
            margin-bottom: 14px;
        }

        .auth-field-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .auth-label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: #2c4263;
            font-size: 11px;
            font-weight: 900;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .auth-label svg {
            flex: 0 0 auto;
            width: 15px;
            height: 15px;
            color: var(--inay-pink);
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .auth-input,
        .auth-select {
            width: 100%;
            min-height: 45px;
            padding: 0 14px;
            color: #07152a;
            background: #ffffff;
            border: 1px solid var(--inay-field);
            border-radius: 11px;
            font-size: 12px;
            font-weight: 700;
        }

        .auth-input::placeholder {
            color: #8ca1c0;
            opacity: 1;
        }

        .auth-input:focus,
        .auth-select:focus {
            outline: 3px solid rgba(236, 10, 120, 0.14);
            border-color: #ff2d91;
        }

        .auth-help {
            margin: 6px 0 0;
            color: #506a8b;
            font-size: 10px;
            line-height: 1.4;
        }

        .location-box {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            margin: 16px 0;
            padding: 17px 15px;
            background: #fbfdff;
            border: 1px solid var(--inay-line);
            border-radius: 14px;
        }

        .location-title {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin: 0 0 8px;
            color: #030813;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.35;
        }

        .location-title svg {
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            color: var(--inay-pink);
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .location-copy {
            margin: 0;
            color: #44638a;
            font-size: 11px;
            line-height: 1.5;
        }

        .location-button {
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            color: #ffffff;
            background: var(--inay-pink);
            border-radius: 11px;
            font-size: 12px;
            white-space: nowrap;
        }

        .location-button svg,
        .auth-submit svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .consent-box {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 66px;
            margin: 16px 0;
            padding: 14px 12px;
            color: var(--inay-text);
            background: #fff6fb;
            border: 1px solid #ffd7e9;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.4;
        }

        .consent-box input {
            width: 15px;
            height: 15px;
            flex: 0 0 auto;
            padding: 0;
            border-radius: 3px;
        }

        .consent-box a {
            color: var(--inay-pink);
            font-weight: 900;
            text-decoration: underline;
        }

        .auth-submit {
            display: inline-flex;
            width: 100%;
            gap: 8px;
            min-height: 46px;
            color: #ffffff;
            background: var(--inay-button-pink);
            border-radius: 9px;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 4px 8px rgba(236, 10, 120, 0.20);
        }

        .auth-submit.login-submit {
            background: var(--inay-pink);
        }

        .auth-submit.requires-consent {
            transition: background 160ms ease, box-shadow 160ms ease;
        }

        .auth-submit.requires-consent.is-ready {
            background: var(--inay-pink);
            box-shadow: 0 6px 12px rgba(236, 10, 120, 0.24);
        }

        .auth-footer-link {
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px solid var(--inay-line);
            color: var(--inay-pink);
            font-size: 12px;
            font-weight: 900;
            text-align: center;
        }

        .auth-footer-link a {
            color: var(--inay-pink);
            font-weight: 900;
        }

        .auth-alert {
            width: min(420px, 100%);
            margin: 0 auto 16px;
            font-size: 13px;
        }

        .auth-error {
            margin: 0 0 16px;
            font-size: 12px;
        }

        @media (max-width: 700px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 16px;
            }

            .two-columns, .details {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 22px;
            }
        }

        @media (max-width: 1024px) {
            .portal-sidebar {
                transform: translateX(-100%);
            }

            .portal-header {
                left: 0;
                padding: 0 18px;
            }

            .portal-main {
                padding: 96px 18px 32px;
            }

            .portal-menu-button,
            .portal-close {
                display: inline-flex;
            }

            .portal-drawer-check:checked ~ .portal-sidebar {
                transform: translateX(0);
            }

            .portal-drawer-check:checked ~ .portal-overlay {
                display: block;
            }

            .maternal-vitals-grid,
            .maternal-guidelines-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .maternal-dashboard-grid,
            .maternal-bottom-grid {
                grid-template-columns: 1fr;
            }

            .casefiles-grid {
                grid-template-columns: repeat(2, minmax(260px, 1fr));
            }

            .casefile-profile-actions {
                margin-top: 0;
                justify-content: flex-start;
            }

            .casefile-profile-facts,
            .casefile-status-grid,
            .casefile-progress-cards,
            .casefile-chart-grid,
            .casefile-record-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .casefile-journey-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .casefiles-filter-form,
            .casefile-detail-grid {
                grid-template-columns: 1fr;
            }

            .casefile-modal {
                padding: 18px;
            }

            .casefile-modal-dialog {
                width: calc(100vw - 28px);
                max-height: calc(100vh - 28px);
            }

            .vitals-field-grid {
                grid-template-columns: 1fr;
            }

            .history-dialog {
                width: calc(100vw - 24px);
            }
        }

        @media (max-width: 760px) {
            .kaalaman-hero,
            .kaalaman-trimester-grid,
            .kaalaman-info-grid,
            .kaalaman-video-grid,
            .kaalaman-status-grid,
            .kaalaman-lower-grid,
            .kaalaman-library-grid,
            .kaalaman-upload-row {
                grid-template-columns: 1fr;
            }

            .maternal-monitoring-heading,
            .maternal-panel-title,
            .maternal-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .maternal-vitals-grid,
            .maternal-stat-grid,
            .maternal-stat-grid.is-three,
            .maternal-guidelines-grid {
                grid-template-columns: 1fr;
            }

            .maternal-heading-actions {
                justify-content: flex-start;
            }

            .casefiles-heading,
            .casefile-detail-hero,
            .casefile-card-top {
                flex-direction: column;
            }

            .casefiles-stats,
            .casefiles-grid,
            .casefile-facts.is-detail,
            .casefile-vital-grid,
            .casefile-summary-strip,
            .casefile-profile-facts,
            .casefile-status-grid,
            .casefile-tabs,
            .casefile-vital-grid.is-large,
            .casefile-progress-cards,
            .casefile-chart-grid,
            .casefile-record-row {
                grid-template-columns: 1fr;
            }

            .casefile-detail-heading,
            .casefile-profile-main,
            .casefile-panel-title {
                align-items: flex-start;
                flex-direction: column;
            }

            .casefile-journey-grid {
                grid-template-columns: 1fr;
            }

            .casefile-risk {
                margin-left: 0;
            }

            .casefile-card-actions {
                flex-direction: column;
            }

            .casefile-icon-action {
                width: 100%;
            }

            .casefile-add-button {
                width: 100%;
            }

            .casefile-modal-header,
            .casefile-modal-footer {
                align-items: flex-start;
                flex-direction: column;
                padding: 22px 18px 14px;
            }

            .casefile-modal-title h2 {
                font-size: 24px;
            }

            .casefile-modal-search,
            .casefile-modal-info {
                margin-right: 18px;
                margin-left: 18px;
            }

            .casefile-patient-list {
                padding: 16px 18px;
            }

            .casefile-patient-row {
                align-items: stretch;
                flex-direction: column;
            }

            .casefile-patient-row input[type="checkbox"] {
                align-self: flex-end;
            }

            .casefile-modal-footer div,
            .casefile-modal-secondary,
            .casefile-modal-submit {
                width: 100%;
            }

            .vitals-modal {
                padding: 12px;
            }

            .vitals-dialog {
                width: calc(100vw - 24px);
            }

            .vitals-dialog-footer {
                flex-direction: column;
            }

            .vitals-cancel,
            .vitals-save {
                width: 100%;
            }

            .history-modal {
                padding: 12px;
            }

            .history-dialog {
                width: calc(100vw - 24px);
                max-height: 85vh;
                border-radius: 12px;
            }

            .history-dialog-header {
                padding: 18px 18px 14px;
            }

            .history-dialog-title {
                font-size: 19px;
            }

            .history-dialog-controls {
                grid-template-columns: 1fr;
                padding: 14px 18px 8px;
            }

            .history-count-copy {
                padding: 0 18px 12px;
            }

            .history-dialog-body {
                padding: 0 18px 18px;
            }

            .history-table {
                min-width: 640px;
            }

            .history-table th,
            .history-table td {
                padding: 12px 14px;
            }

            .kaalaman-hero,
            .kaalaman-month-body {
                padding: 18px;
            }

            .kaalaman-library-heading {
                flex-direction: column;
            }

            .kaalaman-library-heading h1 {
                font-size: 26px;
            }

            .kaalaman-read-panel {
                align-items: stretch;
                flex-direction: column;
            }

            .kaalaman-button {
                width: 100%;
            }

            .portal-header {
                gap: 10px;
            }

            .portal-page-kicker {
                display: none;
            }

            .portal-page-title {
                max-width: 44vw;
                font-size: 17px;
            }

            .portal-status-badge,
            .portal-header-copy {
                display: none;
            }

            .portal-header-right {
                gap: 7px;
            }

            .portal-header-icon,
            .portal-menu-button {
                width: 40px;
                height: 40px;
            }

            .portal-header-profile .portal-avatar {
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 460px) {
            .auth-page {
                width: min(100% - 24px, 420px);
            }

            .auth-card {
                padding: 24px 18px;
            }

            .auth-field-row,
            .location-box {
                grid-template-columns: 1fr;
            }

            .location-button {
                width: 100%;
            }
        }
    </style>
    @stack('styles')
</head>
@php
    $portalRole = session('auth_role');
    $isMotherPortal = $portalRole === 'mother';
    $isStaffPortal = $portalRole === 'staff';
    $hasPortalShell = $isMotherPortal || $isStaffPortal;
    $portalName = session('auth_name', 'Project INAY User');
    $portalEmail = session('auth_email');
    $nameParts = preg_split('/\s+/', trim($portalName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $portalInitials = '';

    foreach (array_slice($nameParts, 0, 2) as $namePart) {
        $portalInitials .= strtoupper(substr($namePart, 0, 1));
    }

    $portalInitials = $portalInitials ?: 'IN';
    $motherRecord = $mother ?? null;
    $staffRecord = $staff ?? null;
    $pregnancyStatus = $motherRecord->pregnancy_status ?? null;
    $pregnancyLabel = match ($pregnancyStatus) {
        'pregnant' => 'Pregnant',
        'postpartum' => 'Postpartum',
        'planning' => 'Planning pregnancy',
        'not_pregnant' => 'Not pregnant',
        default => 'Pregnancy status pending',
    };
    $monitoringStatus = $pregnancyStatus === 'pregnant'
        ? 'Pregnancy monitoring active'
        : 'Care profile active';
    $sectionPortalTitle = trim($__env->yieldContent('portal_title'));
    $pageTitle = $sectionPortalTitle ?: ($isStaffPortal ? 'Program Staff Dashboard' : 'Mother Dashboard');
    $portalKicker = $isStaffPortal ? 'Program Staff Portal' : 'Mother Portal';
    $portalBrandTitle = $isStaffPortal ? 'Program Staff Portal' : 'Project INAY';
    $portalBrandSubtitle = $isStaffPortal ? 'Clinical Monitoring System' : 'Maternal & Child Health';
    $portalRoleLabel = $isStaffPortal ? ($staffRecord->position ?? 'Program Staff') : 'Mother';
    $portalNotificationCount = $hasPortalShell
        ? \App\Models\AppNotification::where('recipient_id', session('auth_id'))
            ->where('recipient_role', $isStaffPortal ? 'program_staff' : 'mother')
            ->whereNull('read_at')
            ->count()
        : 0;
    $portalIconSvgs = [
        'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 5.6a5.4 5.4 0 0 0-7.6 0L12 6.8l-1.2-1.2a5.4 5.4 0 1 0-7.6 7.6l1.2 1.2L12 22l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z"/></svg>',
        'hospital' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v4"/><path d="M14 9h-4"/><path d="M18 21V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M18 11h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h2"/></svg>',
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>',
        'activity' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        'baby' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 12a7 7 0 1 1-14 0c0-2.2 1-4.1 2.6-5.4"/><path d="M9 5c1.2-2 3.5-2.6 5.5-1.5"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 21a2 2 0 0 0 3.4 0"/><path d="M4 17h16"/><path d="M6 17c1.2-1.2 1.8-2.7 1.8-7a4.2 4.2 0 1 1 8.4 0c0 4.3.6 5.8 1.8 7"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
        'services' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6v12"/><path d="M6 12h12"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>',
        'message' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
        'report' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 17v-4"/><path d="M12 17v-7"/><path d="M16 17v-2"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>',
        'x' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
        'chevron' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3h.1a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.6 1.6 0 0 0 1 1.5h.1a1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8v.1a1.6 1.6 0 0 0 1.5 1h.2a2 2 0 1 1 0 4h-.2a1.6 1.6 0 0 0-1.5 1Z"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>',
    ];

    $motherNavItems = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('mother.dashboard'), 'active' => request()->routeIs('mother.dashboard')],
        ['label' => 'Maternal Monitoring', 'icon' => 'activity', 'href' => route('maternal-monitoring'), 'active' => request()->routeIs('maternal-monitoring')],
        ['label' => 'Child Health', 'icon' => 'baby', 'href' => route('child-health'), 'active' => request()->routeIs('child-health')],
        ['label' => 'Notifications', 'icon' => 'bell'],
        ['label' => 'Clinic Schedule', 'icon' => 'calendar', 'href' => route('mother.clinic-schedule.index'), 'active' => request()->routeIs('mother.clinic-schedule.*')],
        ['label' => 'INAY Kaalaman', 'icon' => 'book', 'href' => route('inay-kaalaman'), 'active' => request()->routeIs('inay-kaalaman*')],
        ['label' => 'Health Services', 'icon' => 'services', 'href' => route('health-services'), 'active' => request()->routeIs('health-services')],
        ['label' => 'Consultation', 'icon' => 'message', 'href' => route('mother.consultation'), 'active' => request()->routeIs('mother.consultation')],
    ];
    $staffNavItems = [
        ['label' => 'Monitor Desk', 'icon' => 'dashboard', 'href' => route('staff.dashboard'), 'active' => request()->routeIs('staff.dashboard')],
        ['label' => 'Mothers Casefiles', 'icon' => 'users', 'href' => route('staff.mothers'), 'active' => request()->routeIs('staff.mothers*')],
        ['label' => 'Neonatal & Vaccines', 'icon' => 'baby', 'href' => route('staff.neonatal'), 'active' => request()->routeIs('staff.neonatal*')],
        ['label' => 'Clinic Schedule', 'icon' => 'calendar', 'href' => route('staff.clinic-schedule.index'), 'active' => request()->routeIs('staff.clinic-schedule.*')],
        ['label' => 'Consultation', 'icon' => 'message', 'href' => route('staff.consultation'), 'active' => request()->routeIs('staff.consultation')],
        ['label' => 'Dynamic Reports', 'icon' => 'report'],
    ];
    $portalNavItems = $isStaffPortal ? $staffNavItems : $motherNavItems;
@endphp
<body class="@yield('body_class') @if($hasPortalShell) portal-shell portal-{{ $portalRole }} @endif">
    @hasSection('auth_screen')
    @else
        @if ($hasPortalShell)
            <input class="portal-drawer-check" type="checkbox" id="portal-drawer-toggle" aria-hidden="true">

            <header class="portal-header">
                <div class="portal-header-left">
                    <label class="portal-menu-button" for="portal-drawer-toggle" aria-label="Open portal navigation">
                        {!! $portalIconSvgs['menu'] !!}
                    </label>
                    <div>
                        <p class="portal-page-kicker">{{ $portalKicker }}</p>
                        <h1 class="portal-page-title">{{ $pageTitle }}</h1>
                    </div>
                </div>

                <div class="portal-header-right">
                    @if ($isMotherPortal)
                        <span class="portal-status-badge">
                            {!! $portalIconSvgs['shield'] !!}
                            {{ $monitoringStatus }}
                        </span>
                    @endif

                    <button class="portal-header-icon" type="button" aria-label="Notifications">
                        {!! $portalIconSvgs['bell'] !!}
                        @if ($portalNotificationCount > 0)
                            <span class="portal-notification-count">{{ $portalNotificationCount > 99 ? '99+' : $portalNotificationCount }}</span>
                        @endif
                    </button>

                    <div class="portal-header-profile">
                        <span class="portal-avatar">{{ $portalInitials }}</span>
                        <div class="portal-header-copy">
                            <div class="portal-header-name">{{ $portalName }}</div>
                            <span class="portal-role-badge">{{ $portalRoleLabel }}</span>
                        </div>
                    </div>

                    <details class="portal-profile-menu">
                        <summary class="portal-profile-summary" aria-label="Open profile menu">
                            {!! $portalIconSvgs['chevron'] !!}
                        </summary>
                        <div class="portal-dropdown">
                            <button class="portal-dropdown-item is-muted" type="button" aria-disabled="true">
                                {!! $portalIconSvgs['user'] !!}
                                My Profile
                            </button>
                            <button class="portal-dropdown-item is-muted" type="button" aria-disabled="true">
                                {!! $portalIconSvgs['settings'] !!}
                                Settings
                            </button>
                            <form class="portal-dropdown-form" method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="portal-dropdown-item is-danger" type="submit">
                                    {!! $portalIconSvgs['logout'] !!}
                                    Logout
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <aside class="portal-sidebar" aria-label="{{ $portalKicker }} navigation">
                <div class="portal-brand">
                    <a class="portal-brand-link" href="{{ $isStaffPortal ? route('staff.dashboard') : route('mother.dashboard') }}">
                        <span class="portal-mark">
                            {!! $portalIconSvgs[$isStaffPortal ? 'hospital' : 'heart'] !!}
                        </span>
                        <span>
                            @if ($isStaffPortal)
                                <span class="portal-brand-kicker">Project INAY</span>
                            @endif
                            <span class="portal-brand-title">{{ $portalBrandTitle }}</span>
                            <span class="portal-brand-subtitle">{{ $portalBrandSubtitle }}</span>
                        </span>
                    </a>
                    <label class="portal-close" for="portal-drawer-toggle" aria-label="Close portal navigation">
                        {!! $portalIconSvgs['x'] !!}
                    </label>
                </div>

                @if ($isMotherPortal)
                    <section class="portal-profile-card" aria-label="Mother profile summary">
                        <div class="portal-profile-row">
                            <span class="portal-avatar">{{ $portalInitials }}</span>
                            <div>
                                <p class="portal-profile-name">{{ $portalName }}</p>
                                <div class="portal-status-line">
                                    {!! $portalIconSvgs['shield'] !!}
                                    <span>{{ $monitoringStatus }}</span>
                                </div>
                                <span class="portal-pregnancy-badge">{{ $pregnancyLabel }}</span>
                            </div>
                        </div>
                    </section>
                    <p class="portal-sidebar-label">Mother Care Navigation</p>
                @else
                    <p class="portal-sidebar-label">Clinical Navigation</p>
                @endif

                <nav class="portal-nav">
                    <ul class="portal-nav-list">
                        @foreach ($portalNavItems as $navItem)
                            <li>
                                @if (! empty($navItem['href']))
                                    <a class="portal-nav-item @if(! empty($navItem['active'])) is-active @endif" href="{{ $navItem['href'] }}">
                                        <span class="portal-icon">{!! $portalIconSvgs[$navItem['icon']] !!}</span>
                                        <span>{{ $navItem['label'] }}</span>
                                    </a>
                                @else
                                    <span class="portal-nav-item is-disabled" aria-disabled="true">
                                        <span class="portal-icon">{!! $portalIconSvgs[$navItem['icon']] !!}</span>
                                        <span>{{ $navItem['label'] }}</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="portal-sidebar-bottom">
                    <form class="portal-logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="portal-logout-button" type="submit">
                            {!! $portalIconSvgs['logout'] !!}
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <label class="portal-overlay" for="portal-drawer-toggle" aria-label="Close portal navigation"></label>
        @else
            <header class="topbar">
                <a class="brand" href="{{ route('home') }}">Project INAY</a>
                <nav class="nav">
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('mother.register') }}">Mother Register</a>
                    <a href="{{ route('staff.register') }}">Staff Register</a>
                </nav>
            </header>
        @endif
    @endif

    <main class="@hasSection('auth_screen') auth-page @else page @if($hasPortalShell) portal-main @endif @endif">
        @if (session('status'))
            <div class="alert @hasSection('auth_screen') auth-alert @endif">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>

