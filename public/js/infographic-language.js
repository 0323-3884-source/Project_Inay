(() => {
    const catalog = document.querySelector('[data-infographic-translations]');
    if (!catalog) return;
    const pairs = [
        ...JSON.parse(catalog.textContent),
        ['INFOGRAPHICS', 'MGA INFOGRAPHIC'],
        ['Read at your own pace. Your progress is saved automatically.', 'Basahin sa iyong sariling bilis. Awtomatikong mase-save ang iyong progreso.'],
        ['Maternal Care', 'Pangangalaga ng Ina'], ['Prenatal Care', 'Pangangalaga Bago Manganak'],
        ['Nutrition', 'Nutrisyon'], ['Labor and Delivery', 'Pagle-labor at Panganganak'],
        ['Postpartum Care', 'Pangangalaga Pagkatapos Manganak'], ['Neonatal Care', 'Pangangalaga sa Bagong Silang'],
        ['Vaccination', 'Pagbabakuna'], ['Health guide', 'Gabay sa kalusugan'],
        ['✓ Learn', '✓ Alamin'], ['✓ Care', '✓ Alagaan'], ['✓ Prepare', '✓ Maghanda'],
        ['View Infographic', 'Tingnan ang Infographic'],
        ['Not started', 'Hindi pa nasisimulan'], ['In progress', 'Kasalukuyang Ginagawa'],
        ['Completed', 'Natapos'], ['✓ Completed', '✓ Natapos'],
        ['Remember, Mom', 'Tandaan, Nanay'],
        ['If you have unusual symptoms or an emergency, consult the nearest Barangay Health Station, RHU, health center, or hospital immediately.', 'Kung may kakaibang sintomas o emergency, agad kumonsulta sa pinakamalapit na Barangay Health Station, RHU, health center, o ospital.'],
        ['For education only. This does not replace professional medical advice, assessment, or treatment. Follow your healthcare provider’s advice.', 'Para sa edukasyon lamang. Hindi ito kapalit ng propesyonal na payong medikal, pagsusuri, o paggamot. Sundin ang payo ng iyong healthcare provider.'],
        ['Sources:', 'Mga sanggunian:'],
        ['♥ You have reached the end of the infographic.', '♥ Nakarating ka na sa dulo ng infographic.'],
        ['Back', 'Bumalik'], ['Retry saving', 'Subukang i-save muli'],
        ['Your progress could not be saved. Check your connection and try again.', 'Hindi na-save ang progreso. Suriin ang koneksyon at subukang muli.'],
        ['Saving progress…', 'Sine-save ang progreso…'],
        ['✓ Completed — your progress has been saved.', '✓ Natapos — na-save na ang iyong progreso.'],
        ['Read to the end to complete this automatically.', 'Basahin hanggang sa dulo para awtomatikong makumpleto.'],
        ['The image could not load. Close and reopen it to try again.', 'Hindi ma-load ang larawan. Isara at buksan muli upang subukan ulit.'],
        ['The infographic could not load. It will not be marked completed yet.', 'Hindi ma-load ang infographic. Hindi pa ito mamarkahang natapos.'],
        ['Open the original image ↗', 'Buksan ang orihinal na larawan ↗'],
        ['Infographic content', 'Nilalaman ng infographic'], ['Close infographic', 'Isara ang infographic'],
    ];
    const translations = new Map();
    pairs.forEach(([en, tl]) => { translations.set(en, { en, tl }); translations.set(tl, { en, tl }); });
    let language = 'en';
    try { language = localStorage.getItem('project-inay-language') || localStorage.getItem('project-inay-kaalaman-language') || 'en'; } catch { /* Use English when storage is blocked. */ }
    const roots = [...document.querySelectorAll('.inay-infographic-library, .inay-infographic-viewer')];
    const translate = (text) => {
        const value = text.trim();
        const pair = translations.get(value);
        if (pair) return text.replace(value, pair[language === 'tl' ? 'tl' : 'en']);
        for (const prefix of ['View Infographic: ', 'Tingnan ang Infographic: ', 'Preview: ', 'Paunang tingin: ', 'PROJECT INAY · ']) {
            if (value.startsWith(prefix)) {
                const translatedPrefix = prefix === 'PROJECT INAY · ' ? prefix
                    : prefix.includes('Infographic') ? (language === 'tl' ? 'Tingnan ang Infographic: ' : 'View Infographic: ')
                    : (language === 'tl' ? 'Paunang tingin: ' : 'Preview: ');
                return text.replace(value, translatedPrefix + translate(value.slice(prefix.length)));
            }
        }
        // Category badges may include a calendar month. Keep that metadata intact.
        if (value.includes(' · ')) return text.replace(value, value.split(' · ').map(translate).join(' · '));
        return text;
    };
    const apply = (node) => {
        if (node.nodeType === Node.TEXT_NODE) {
            if (node.parentElement?.closest('script, style')) return;
            const translated = translate(node.nodeValue);
            if (translated !== node.nodeValue) node.nodeValue = translated;
            return;
        }
        if (!(node instanceof Element) || node.matches('script, style')) return;
        const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) apply(walker.currentNode);
        [node, ...node.querySelectorAll('[aria-label], [alt]')].forEach((element) => {
            for (const attribute of ['aria-label', 'alt']) {
                const value = element.getAttribute(attribute);
                if (value === null) continue;
                const translated = translate(value);
                if (translated !== value) element.setAttribute(attribute, translated);
            }
        });
    };
    const refresh = (value) => { language = value === 'tl' ? 'tl' : 'en'; roots.forEach(apply); };
    const observer = new MutationObserver((records) => {
        for (const record of records) {
            if (record.type === 'childList') record.addedNodes.forEach(apply);
            else apply(record.target);
        }
    });
    roots.forEach((root) => observer.observe(root, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['alt', 'aria-label'] }));
    window.addEventListener('inay:language-change', (event) => refresh(event.detail.language));
    window.InayInfographicLanguage = { refresh: () => refresh(window.InayLanguage?.get() || language) };
    refresh(language);
})();
