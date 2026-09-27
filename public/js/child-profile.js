(() => {
    const ns = 'http://www.w3.org/2000/svg';
    const number = value => Number(value.toFixed(2)).toString();
    document.querySelectorAll('[data-child-growth-chart]').forEach(container => {
        const data = JSON.parse(container.dataset.chart);
        if (!data.has) return;
        const svg = container.querySelector('svg');
        const tooltip = container.querySelector('.child-growth-tooltip');
        const color = container.dataset.unit === 'kg' ? '#2563eb' : '#7c3aed';
        const draw = () => {
            const width = container.clientWidth;
            if (!width) return;
            const height = 260;
            const left = 54, right = width - 24, top = 28, bottom = height - 58;
            const x = age => left + (age - data.min_age) / (data.max_age - data.min_age) * (right - left);
            const y = value => bottom - (value - data.min) / (data.max - data.min) * (bottom - top);
            svg.replaceChildren();
            tooltip.hidden = true;
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            const add = (tag, attrs, text) => {
                const el = document.createElementNS(ns, tag);
                Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value));
                if (text !== undefined) el.textContent = text;
                svg.append(el);
                return el;
            };
            for (let i = 0; i < 3; i++) {
                const value = data.min + (data.max - data.min) * i / 2;
                add('line', {x1: left, x2: right, y1: y(value), y2: y(value), stroke: '#dbe5f1', 'stroke-dasharray': '5 5'});
                add('text', {x: left - 10, y: y(value) + 4, 'text-anchor': 'end'}, number(value));
                const age = data.min_age + (data.max_age - data.min_age) * i / 2;
                add('text', {x: x(age), y: bottom + 24, 'text-anchor': 'middle'}, number(age));
            }
            add('path', {d: `M${left} ${top}V${bottom}H${right}`, stroke: '#94a3b8', fill: 'none'});
            add('text', {x: (left + right) / 2, y: height - 12, 'text-anchor': 'middle', class: 'child-growth-axis-title'}, 'Age (months)');
            if (data.points.length > 1) add('polyline', {points: data.points.map(p => `${x(p.age)},${y(p.value)}`).join(' '), fill: 'none', stroke: color, 'stroke-width': 2});
            data.points.forEach(point => {
                const label = `${number(point.value)} ${container.dataset.unit} · Age ${number(point.age)} months · ${point.date}`;
                const dot = add('circle', {cx: x(point.age), cy: y(point.value), r: 6, fill: '#fff', stroke: color, 'stroke-width': 3, tabindex: 0, role: 'button', 'aria-label': label});
                const show = () => { tooltip.textContent = label; tooltip.hidden = false; };
                dot.addEventListener('mouseenter', show);
                dot.addEventListener('focus', show);
                dot.addEventListener('click', show);
                dot.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); show(); }
                    if (event.key === 'Escape') tooltip.hidden = true;
                });
            });
        };
        new ResizeObserver(draw).observe(container);
    });
})();
