// Gráficos del portal y del admin (Chart.js). Cada <figure data-chart> trae
// sus datos en JSON; la tabla con los mismos datos queda siempre en la
// página (sin JavaScript o para lectores de pantalla).
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, Filler, LinearScale, LineController, LineElement, PointElement, Tooltip);

const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

// Colores del tema actual (claro u oscuro, cada uno con sus propios tonos)
const theme = () => ({
    series: { 1: css('--chart-1'), 2: css('--chart-2'), neutral: css('--chart-neutral') },
    grid: css('--chart-grid'),
    surface: css('--chart-surface'),
    text: css('--muted'),
    strong: css('--text'),
});

const number = (value, unit) => `${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(value)}${unit === '%' ? ' %' : ''}`;

// Línea vertical que sigue al puntero en los gráficos de línea
const crosshair = {
    id: 'crosshair',
    afterDatasetsDraw(chart) {
        const active = chart.tooltip?.getActiveElements?.();
        if (chart.config.type !== 'line' || !active?.length) return;
        const { ctx, chartArea } = chart;
        const x = active[0].element.x;
        ctx.save();
        ctx.strokeStyle = theme().text;
        ctx.globalAlpha = 0.5;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, chartArea.top);
        ctx.lineTo(x, chartArea.bottom);
        ctx.stroke();
        ctx.restore();
    },
};

function tooltip(t, unit, extra) {
    return {
        backgroundColor: t.surface,
        titleColor: t.text,
        bodyColor: t.strong,
        borderColor: t.grid,
        borderWidth: 1,
        padding: 10,
        displayColors: true,
        boxWidth: 10,
        boxHeight: 2,
        callbacks: {
            label: (item) => ` ${number(item.parsed[item.chart.options.indexAxis === 'y' ? 'x' : 'y'], unit)}  ${item.dataset.label ?? ''}${extra ? extra(item) : ''}`,
        },
    };
}

function axes(t, { percent, horizontal, stacked }) {
    const value = {
        stacked,
        beginAtZero: true,
        max: percent ? 100 : undefined,
        grid: { color: t.grid, drawTicks: false },
        border: { display: false },
        ticks: { color: t.text, padding: 6, callback: (v) => number(v, percent ? '%' : ''), maxTicksLimit: 6 },
    };
    const category = {
        stacked,
        grid: { display: false },
        border: { color: t.grid },
        ticks: { color: t.text, autoSkip: true, maxRotation: 0 },
    };

    return horizontal ? { x: value, y: category } : { x: category, y: value };
}

function build(figure) {
    const data = JSON.parse(figure.querySelector('script[type="application/json"]').textContent);
    const canvas = figure.querySelector('canvas');
    const t = theme();
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 300 },
        plugins: { legend: { display: false } },
    };

    if (data.type === 'line') {
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    data: data.values,
                    borderColor: t.series[1],
                    backgroundColor: `${t.series[1]}1A`,
                    fill: true,
                    borderWidth: 2,
                    tension: 0.25,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: t.series[1],
                    pointBorderColor: t.surface,
                    pointBorderWidth: 2,
                    pointHitRadius: 14,
                }],
            },
            options: {
                ...base,
                interaction: { mode: 'index', intersect: false },
                scales: axes(t, { percent: data.unit === '%' }),
                plugins: {
                    ...base.plugins,
                    tooltip: tooltip(t, data.unit, (item) => (data.details ? ` · ${data.details[item.dataIndex]}` : '')),
                },
            },
            plugins: [crosshair],
        });
    }

    const horizontal = data.type !== 'column';
    const stacked = Boolean(data.series);
    const datasets = stacked
        ? data.series.map((s, i) => ({ label: s.label, data: s.values, backgroundColor: t.series[s.color], order: i }))
        : [{ label: data.label, data: data.values, backgroundColor: t.series[1] }];

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: datasets.map((d) => ({
                ...d,
                maxBarThickness: 24,
                // 2 px del color de fondo separan los segmentos (no un borde)
                borderColor: t.surface,
                borderWidth: stacked ? { right: horizontal ? 2 : 0, top: horizontal ? 0 : 2 } : 0,
                borderSkipped: 'start',
                // Extremo de datos redondeado (4 px); la línea base queda recta (borderSkipped)
                // y en las barras apiladas solo se redondea el final de la pila
                borderRadius: 4,
            })),
        },
        options: {
            ...base,
            indexAxis: horizontal ? 'y' : 'x',
            interaction: { mode: stacked ? 'index' : 'nearest', intersect: !stacked, axis: horizontal ? 'y' : 'x' },
            scales: axes(t, { percent: data.unit === '%', horizontal, stacked }),
            plugins: { ...base.plugins, tooltip: tooltip(t, data.unit) },
        },
    });
}

const charts = new Map();

function renderAll() {
    document.querySelectorAll('[data-chart]').forEach((figure) => {
        charts.get(figure)?.destroy();
        charts.set(figure, build(figure));
    });
}

renderAll();

// El modo oscuro tiene sus propios tonos: se vuelve a dibujar al cambiar el tema
new MutationObserver(renderAll).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
