(() => {
    // Only follow read-only routes. Never crawl account actions or external providers.
    const pagePath = /^(?:inay-kaalaman(?:\/(?:videos\/\d+|infographics\/\d+\/pdf))?|mother\/(?:dashboard|documents(?:\/\d+\/(?:preview|download))?|profile|settings|consultation|clinic-schedule)|maternal-monitoring|child-health|health-services|staff\/(?:dashboard|profile|mothers(?:\/\d+)?|neonatal-vaccines|dynamic-reports|clinic-schedule|offline-records))$/;
    const assetPath = /^(?:css|js|build|pwa|images|storage|fonts)\//;
    window.InayOfflineResources = {
        normalize(value, page, base, asset = false) {
            try {
                const url = new URL(value, page);
                if (url.origin !== base.origin || !url.pathname.startsWith(base.pathname)) return null;
                const path = url.pathname.slice(base.pathname.length);
                if (!(assetPath.test(path) || (!asset && pagePath.test(path)))) return null;
                url.hash = '';
                if (url.searchParams.get('source') === 'pwa') url.searchParams.delete('source');
                return url.href;
            } catch { return null; }
        },
        discover(page, enqueue) {
            page.querySelectorAll('a[href], option[value]').forEach(node => enqueue(node.getAttribute('href') || node.getAttribute('value')));
            page.querySelectorAll('script[src], img[src], link[rel="stylesheet"][href], link[rel="icon"][href], source[src], video[poster]').forEach(node => {
                enqueue(node.getAttribute('src') || node.getAttribute('href') || node.getAttribute('poster'), true);
            });
            page.querySelectorAll('[data-preview], [data-download]').forEach(node => {
                for (const name of ['data-preview', 'data-download']) enqueue(node.getAttribute(name));
            });
            // Learning images are inside inert templates until the reader is opened.
            page.querySelectorAll('template').forEach(template => this.discover(template.content, enqueue));
            page.querySelectorAll('style, [style]').forEach(node => this.css(node.tagName === 'STYLE' ? node.textContent : node.getAttribute('style'), enqueue));
        },
        css(text, enqueue) {
            for (const match of text.matchAll(/url\(\s*['"]?([^'"\s)]+)['"]?\s*\)|@import\s+['"]([^'"]+)['"]/g)) enqueue(match[1] || match[2], true);
        },
    };
})();
