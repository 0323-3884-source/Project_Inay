(() => {
    const button = document.querySelector('[data-newborn-report-print]') || document.querySelector('[data-statistics-report-print]');
    const template = document.getElementById('newborn-clinical-report') || document.getElementById('statistics-clinical-report');
    if (!button || !template) return;
    let frame;
    button.addEventListener('click', () => {
        if (button.disabled) return;
        button.disabled = true;
        frame?.remove();
        frame = document.createElement('iframe');
        frame.title = template.id === 'statistics-clinical-report' ? 'Program Staff Clinical Statistics Report' : 'Newborn / Neonatal Clinical Monitoring Report';
        frame.style.cssText = 'position:fixed;left:-10000px;top:0;width:800px;height:1100px;border:0';
        frame.setAttribute('aria-hidden', 'true');
        frame.onload = async () => {
            try {
                await frame.contentDocument.fonts.ready;
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } finally {
                button.disabled = false;
            }
        };
        frame.srcdoc = '<!DOCTYPE html><html lang="en">' + template.innerHTML + '</html>';
        document.body.appendChild(frame);
    });
    window.addEventListener('pagehide', () => frame?.remove());
})();
