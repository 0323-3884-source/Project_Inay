(() => {
    const links = Array.from(document.querySelectorAll('[data-learning-month-link]'));
    const months = Array.from(document.querySelectorAll('[data-learning-month]'));
    if (!links.length || !months.length) return;

    const selectMonth = (month, navigate = false) => {
        const selected = months.find((item) => item.dataset.learningMonth === month);
        if (!selected) return;
        months.forEach((item) => { item.hidden = item !== selected; });
        links.forEach((link) => {
            if (link.dataset.learningMonthLink === month) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
        if (navigate) {
            document.querySelector('[data-casefile-tab="learning-documents"]')?.click();
            selected.focus({ preventScroll: true });
            selected.scrollIntoView({ block: 'start' });
        }
    };

    const selectFromHash = () => {
        const match = window.location.hash.match(/^#month-documents-(\d+)$/);
        if (match) selectMonth(match[1], true);
        else selectMonth(links[0].dataset.learningMonthLink);
    };

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            if (window.location.hash !== link.hash) window.history.pushState(null, '', link.hash);
            selectMonth(link.dataset.learningMonthLink, true);
        });
    });
    window.addEventListener('hashchange', selectFromHash);
    selectFromHash();
})();
