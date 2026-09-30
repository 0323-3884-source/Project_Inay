<svg viewBox="0 0 24 24" aria-hidden="true">
@switch($icon)
@case('building')<path d="M4 21V3h16v18M2 21h20M9 21v-5h6v5M8 7h2m4 0h2M8 11h2m4 0h2"/>@break
@case('dashboard')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('chart')<path d="M4 3v18h17M8 17v-5m5 5V7m5 10V4"/>@break
@case('report')<path d="M14 3H5v18h14V8l-5-5v5h5M8 12h8M8 16h8"/>@break
@case('check')<rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8 12 3 3 5-6"/>@break
@case('logout')<path d="M9 3H4v18h5M10 12h11m-4-4 4 4-4 4"/>@break
@case('baby')<circle cx="12" cy="12" r="9"/><path d="M9 10h.01M15 10h.01M9 15q3 3 6 0M12 3q-3 4 1 4"/>@break
@default<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 5v2"/>
@endswitch
</svg>
