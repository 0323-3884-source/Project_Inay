(() => {
    const drawer = document.getElementById('portal-drawer-toggle');
    const main = document.querySelector('.portal-main');
    const desktopQuery = window.matchMedia('(min-width: 1025px)');
    let resizeFrame = 0;

    const setDrawerOpen = (open) => {
        if (!drawer) return;
        drawer.checked = open;
        document.body.classList.toggle('portal-drawer-open', open);
    };

    const syncDrawerState = () => {
        if (!drawer) return;
        document.body.classList.toggle('portal-drawer-open', drawer.checked && !desktopQuery.matches);
    };

    const requestChartResize = () => {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(() => {
            if (!window.Chart) return;

            Object.values(window.Chart.instances || {}).forEach((chart) => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        });
    };

    if (drawer) {
        drawer.addEventListener('change', syncDrawerState);

        document.querySelectorAll('.portal-sidebar a[href], .portal-brand-link').forEach((link) => {
            link.addEventListener('click', () => {
                if (!desktopQuery.matches) {
                    setDrawerOpen(false);
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setDrawerOpen(false);
            }
        });

        const handleViewportChange = () => {
            if (desktopQuery.matches) {
                setDrawerOpen(false);
            } else {
                syncDrawerState();
            }

            requestChartResize();
        };

        if (typeof desktopQuery.addEventListener === 'function') {
            desktopQuery.addEventListener('change', handleViewportChange);
        } else if (typeof desktopQuery.addListener === 'function') {
            desktopQuery.addListener(handleViewportChange);
        }
    }

    document.addEventListener('toggle', (event) => {
        const menu = event.target.closest?.('.portal-profile-menu');
        if (!menu || !menu.open) return;

        document.querySelectorAll('.portal-profile-menu[open]').forEach((openMenu) => {
            if (openMenu !== menu) {
                openMenu.removeAttribute('open');
            }
        });
    }, true);

    document.addEventListener('click', (event) => {
        if (event.target.closest('.portal-profile-menu')) return;

        document.querySelectorAll('.portal-profile-menu[open]').forEach((menu) => {
            menu.removeAttribute('open');
        });
    });

    window.addEventListener('resize', requestChartResize, { passive: true });
    window.addEventListener('orientationchange', requestChartResize, { passive: true });

    if (main && 'ResizeObserver' in window) {
        new ResizeObserver(requestChartResize).observe(main);
    }

    syncDrawerState();
    requestChartResize();
})();
