import { Chart, ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js';
import { createIcons } from 'lucide';
import { icons } from './icons';

const renderIcons = () => createIcons({ icons });
renderIcons();

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const rupiah = (value, decimals = 0) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value);
const shortRupiah = (value) => {
    if (Math.abs(value) >= 1e9) return `Rp${(value / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 })} M`;
    if (Math.abs(value) >= 1e6) return `Rp${(value / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    if (Math.abs(value) >= 1e3) return `Rp${(value / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 })} rb`;
    return rupiah(value);
};

/* ---------- Muncul saat discroll dan angka naik ---------- */
if (document.documentElement.classList.contains('js-reveal')) {
    let batch = 0;
    const observer = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            entry.target.style.setProperty('--reveal-index', Math.min(batch++, 6));
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
        requestAnimationFrame(() => { batch = 0; });
    }, { rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('[data-reveal]').forEach((item) => observer.observe(item));
}

function countUp(element) {
    if (reducedMotion || element.dataset.counted) return;
    element.dataset.counted = 'true';
    const final = element.textContent;
    const match = final.match(/^(Rp)?([\d.]+)(?:,(\d+))?/);
    if (!match) return;
    const decimals = match[3]?.length ?? 0;
    const target = Number(`${match[2].replace(/\./g, '')}.${match[3] ?? 0}`);
    if (!target) return;
    const format = new Intl.NumberFormat('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    const start = performance.now() + 200;
    const tick = (now) => {
        const progress = Math.min(Math.max((now - start) / 1200, 0), 1);
        element.textContent = `${match[1] ?? ''}${format.format(target * (1 - Math.pow(1 - progress, 4)))}`;
        if (progress < 1) requestAnimationFrame(tick); else element.textContent = final;
    };
    requestAnimationFrame(tick);
}
if ('IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver((entries) => entries.filter((entry) => entry.isIntersecting).forEach((entry) => { countUp(entry.target); counterObserver.unobserve(entry.target); }), { threshold: 0.6 });
    document.querySelectorAll('.kpi-value .money, .stat strong .money').forEach((counter) => counterObserver.observe(counter));
}

/* ---------- Grafik ---------- */
Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);
Chart.defaults.font.family = '"Plus Jakarta Sans Variable", system-ui, sans-serif';
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.legend.display = false;
const tooltip = { backgroundColor: '#0f2342', padding: 11, cornerRadius: 8, boxPadding: 4, titleFont: { size: 12.5, weight: 600 }, bodyFont: { size: 13, weight: 600 } };

/* Grafik dibuat saat kartunya terlihat supaya animasinya ikut terlihat. */
function whenVisible(element, callback) {
    if (!('IntersectionObserver' in window)) { callback(); return; }
    const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        observer.disconnect();
        setTimeout(callback, 200);
    }, { threshold: 0.25 });
    observer.observe(element);
}

/* Di layar sempit nama bidang dipendekkan supaya label tidak bertumpuk. */
const shortName = (text) => text.replace(/^(Penyelenggaraan|Pelaksanaan|Penanggulangan) /, '').replace(/ (Masyarakat Desa|Kemasyarakatan Desa|Desa)$/, '');
const axisLabel = (canvas, text) => (canvas.clientWidth < 520 ? wrapLabel(shortName(text), 10) : wrapLabel(text));
const wrapLabel = (text, width = 14) => text.split(' ').reduce((lines, word) => {
    const last = lines.at(-1);
    if (last !== undefined && `${last} ${word}`.length <= width) lines[lines.length - 1] = `${last} ${word}`; else lines.push(word);
    return lines;
}, []);

/* Nominal ditulis di atas batang, seperti rancangan dashboard. */
const barValueLabels = {
    id: 'barValueLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.font = '700 11.5px "Plus Jakarta Sans Variable", sans-serif';
        ctx.fillStyle = '#0f172a';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        chart.getDatasetMeta(0).data.forEach((bar, index) => {
            const value = chart.data.datasets[0].data[index];
            ctx.fillText(chart.width < 560 ? shortRupiah(value) : rupiah(value), bar.x, bar.y - 6);
        });
        ctx.restore();
    },
};

const animation = (stagger = 0) => (reducedMotion ? false : { duration: 1000, easing: 'easeOutQuart', delay: (context) => (context.type === 'data' && context.mode === 'default' ? context.dataIndex * stagger : 0) });
const moneyAxis = { beginAtZero: true, grace: '8%', border: { display: false }, grid: { color: '#eef2f7' }, ticks: { maxTicksLimit: 5, callback: (value) => (value === 0 ? 'Rp0' : shortRupiah(value)) } };
const categoryAxis = (canvas) => ({ grid: { display: false }, ticks: { color: '#334155', font: () => ({ size: canvas.clientWidth < 520 ? 10.5 : 12 }), autoSkip: false, maxRotation: 0 } });
const percentOf = (item) => Number(item.percentage).toLocaleString('id-ID', { maximumFractionDigits: 1 });

const charts = {
    doughnut(canvas, items, options = {}) {
        return new Chart(canvas, {
            type: 'doughnut',
            data: { labels: items.map((item) => item.name), datasets: [{ data: items.map((item) => Number(item.amount)), backgroundColor: items.map((item) => item.color), borderColor: '#fff', borderWidth: 3, hoverOffset: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '66%', animation: reducedMotion ? false : { duration: 1100, easing: 'easeOutQuart', animateRotate: true }, plugins: { tooltip: { ...tooltip, callbacks: { label: (context) => ` ${options.count ? `${context.raw} baris` : rupiah(context.raw, 2)} (${percentOf(items[context.dataIndex])}%)` } } } },
        });
    },
    bars(canvas, items) {
        return new Chart(canvas, {
            type: 'bar',
            data: { labels: items.map((item) => axisLabel(canvas, item.name)), datasets: [{ data: items.map((item) => Number(item.amount)), backgroundColor: items.map((item) => item.color), borderRadius: 8, borderSkipped: 'bottom', maxBarThickness: 70, categoryPercentage: 0.72 }] },
            plugins: [barValueLabels],
            options: {
                responsive: true, maintainAspectRatio: false, layout: { padding: { top: 24 } }, animation: animation(110),
                scales: { x: categoryAxis(canvas), y: moneyAxis },
                plugins: { tooltip: { ...tooltip, callbacks: { title: (list) => items[list[0].dataIndex].name, label: (context) => ` ${rupiah(context.raw, 2)} · ${percentOf(items[context.dataIndex])}%` } } },
                onHover: (event, elements) => { event.native.target.style.cursor = elements.length && items[elements[0].index].url ? 'pointer' : 'default'; },
                onClick: (event, elements) => { if (elements.length && items[elements[0].index].url) window.location.href = items[elements[0].index].url; },
            },
        });
    },
    compare(canvas, items) {
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: items.map((item) => axisLabel(canvas, item.name)),
                datasets: [
                    { label: 'Anggaran terverifikasi', data: items.map((item) => Number(item.counted_amount ?? item.amount)), backgroundColor: '#bfdbfe', borderRadius: 6, maxBarThickness: 34 },
                    { label: 'Realisasi', data: items.map((item) => Number(item.realization)), backgroundColor: '#2563eb', borderRadius: 6, maxBarThickness: 34 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: animation(90),
                scales: { x: categoryAxis(canvas), y: moneyAxis },
                plugins: { legend: { display: true, position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, font: { size: 12.5, weight: 600 } } }, tooltip: { ...tooltip, callbacks: { title: (list) => items[list[0].dataIndex].name, label: (context) => ` ${context.dataset.label}: ${rupiah(context.raw, 2)}` } } },
            },
        });
    },
    monthly(canvas, months) {
        const lastActive = months.reduce((last, month, index) => (month.realized > 0 ? index : last), -1);
        return new Chart(canvas, {
            data: {
                labels: months.map((month) => month.label.slice(0, 3)),
                datasets: [
                    { type: 'line', label: 'Realisasi kumulatif', data: months.map((month, index) => (index <= lastActive ? month.cumulative : null)), borderColor: '#2563eb', backgroundColor: 'rgb(37 99 235 / .12)', fill: true, tension: 0.35, pointRadius: 3.5, pointBackgroundColor: '#2563eb', borderWidth: 2.5 },
                    { type: 'line', label: 'Rencana (dibagi rata)', data: months.map((month) => month.planned), borderColor: '#94a3b8', borderDash: [6, 5], pointRadius: 0, borderWidth: 2, fill: false },
                    { type: 'bar', label: 'Pengeluaran per bulan', data: months.map((month) => month.realized), backgroundColor: '#22c55e', borderRadius: 5, maxBarThickness: 26 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, animation: animation(60),
                scales: { x: { grid: { display: false } }, y: moneyAxis },
                plugins: { legend: { display: true, position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, font: { size: 12.5, weight: 600 } } }, tooltip: { ...tooltip, callbacks: { title: (list) => months[list[0].dataIndex].label, label: (context) => (context.raw === null ? null : ` ${context.dataset.label}: ${rupiah(context.raw)}`) } } },
            },
        });
    },
};

function renderCharts() {
    const data = window.dashboardData ?? {};
    const mount = (id, render) => { const canvas = document.getElementById(id); if (canvas) whenVisible(canvas, () => render(canvas)); };
    if (data.income?.length) mount('income-chart', (canvas) => charts.doughnut(canvas, data.income));
    if (data.expense?.length) mount('expense-chart', (canvas) => charts.bars(canvas, data.expense));
    if (data.expense?.length) mount('compare-chart', (canvas) => charts.compare(canvas, data.expense));
    if (data.funding?.length) mount('funding-chart', (canvas) => charts.doughnut(canvas, data.funding));
    if (data.monthly?.length) mount('monthly-chart', (canvas) => charts.monthly(canvas, data.monthly));
    if (data.status?.length) mount('status-chart', (canvas) => charts.doughnut(canvas, data.status, { count: true }));
}
if (document.fonts?.ready) document.fonts.ready.then(renderCharts); else renderCharts();

/* ---------- Sidebar ---------- */
const sidebar = document.getElementById('sidebar');
const app = document.querySelector('.app');
const desktop = window.matchMedia('(min-width: 1025px)');
const openButton = document.querySelector('[data-sidebar-open]');
const collapseButton = document.querySelector('[data-sidebar-collapse]');
const groupButton = document.querySelector('[data-nav-group]');
const subnav = document.getElementById('apbdes-menu');
const flyout = document.getElementById('apbdes-flyout');
const isRail = () => desktop.matches && document.documentElement.classList.contains('sidebar-collapsed');

function syncSidebar() {
    if (!sidebar) return;
    const mobileOpen = document.body.classList.contains('sidebar-open');
    sidebar.inert = !desktop.matches && !mobileOpen;
    if (app) app.inert = !desktop.matches && mobileOpen;
    openButton?.setAttribute('aria-expanded', String(mobileOpen));
    const collapsed = document.documentElement.classList.contains('sidebar-collapsed');
    collapseButton?.setAttribute('aria-expanded', String(!collapsed));
    const label = collapseButton?.querySelector('.nav-text');
    if (label) label.textContent = collapsed ? 'Lebarkan menu' : 'Ringkas menu';
    if (collapseButton) collapseButton.title = collapsed ? 'Lebarkan menu' : 'Ringkas menu';
}
function setMobile(open) {
    document.body.classList.toggle('sidebar-open', open);
    syncSidebar();
    if (open) sidebar.querySelector('a, button')?.focus(); else openButton?.focus();
}
openButton?.addEventListener('click', () => setMobile(true));
document.querySelectorAll('[data-sidebar-close]').forEach((element) => element.addEventListener('click', () => setMobile(false)));
collapseButton?.addEventListener('click', () => {
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('apbdes.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch { /* penyimpanan tidak tersedia */ }
    if (flyout?.matches(':popover-open')) flyout.hidePopover();
    syncSidebar();
});
groupButton?.addEventListener('click', () => {
    if (isRail() && flyout) {
        const rect = groupButton.getBoundingClientRect();
        flyout.style.left = `${rect.right + 12}px`;
        flyout.style.top = `${rect.top}px`;
        flyout.togglePopover();
        return;
    }
    const expanded = groupButton.getAttribute('aria-expanded') === 'true';
    groupButton.setAttribute('aria-expanded', String(!expanded));
    subnav.hidden = expanded;
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) setMobile(false);
});
desktop.addEventListener('change', () => { document.body.classList.remove('sidebar-open'); syncSidebar(); });
syncSidebar();

/* Menu perangkat desa ada di bawah; geser daftar menu agar halaman yang aktif terlihat. */
const sidebarNav = sidebar?.querySelector('.sidebar-nav');
const activeNavLink = sidebarNav?.querySelector('.nav-link.is-active, .subnav a.is-active');
if (sidebarNav && activeNavLink) {
    const navBox = sidebarNav.getBoundingClientRect();
    const linkBox = activeNavLink.getBoundingClientRect();
    if (linkBox.bottom > navBox.bottom || linkBox.top < navBox.top) sidebarNav.scrollTop += linkBox.top - navBox.top - (navBox.height - linkBox.height) / 2;
}

/* ---------- Menu profil ---------- */
const profileMenu = document.getElementById('profile-menu');
const profileTrigger = document.querySelector('[data-profile-trigger]');
const placeBelow = (popover, trigger) => {
    const rect = trigger.getBoundingClientRect();
    popover.style.top = `${rect.bottom + 8}px`;
    popover.style.right = `${Math.max(12, window.innerWidth - rect.right)}px`;
};
profileMenu?.addEventListener('beforetoggle', (event) => { if (event.newState === 'open') placeBelow(profileMenu, profileTrigger); });
profileMenu?.addEventListener('toggle', (event) => profileTrigger?.setAttribute('aria-expanded', String(event.newState === 'open')));

/* ---------- Lonceng notifikasi ---------- */
const bellButton = document.querySelector('[data-bell]');
const bellPanel = document.querySelector('[data-bell-panel]');
const bellCount = document.querySelector('[data-bell-count]');
const bellList = document.querySelector('[data-bell-list]');
const SEEN_KEY = 'apbdes.notifications.seen';
const readSeen = () => { try { return new Set(JSON.parse(localStorage.getItem(SEEN_KEY) ?? '[]')); } catch { return new Set(); } };
const writeSeen = (ids) => { try { localStorage.setItem(SEEN_KEY, JSON.stringify([...ids].slice(-80))); } catch { /* penyimpanan tidak tersedia */ } };

function syncBell() {
    if (!bellList) return 0;
    const seen = readSeen();
    let unread = 0;
    bellList.querySelectorAll('[data-notification-id]').forEach((item) => {
        const isUnread = !seen.has(item.dataset.notificationId);
        item.classList.toggle('is-unread', isUnread);
        if (isUnread) unread++;
    });
    bellCount.hidden = unread === 0;
    bellCount.textContent = unread > 9 ? '9+' : String(unread);
    bellButton.setAttribute('aria-label', unread ? `Notifikasi, ${unread} belum dibaca` : 'Notifikasi');
    return unread;
}
function markAllRead() {
    const seen = readSeen();
    bellList?.querySelectorAll('[data-notification-id]').forEach((item) => seen.add(item.dataset.notificationId));
    writeSeen(seen);
    syncBell();
}
const escapeHtml = (text) => String(text ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
async function refreshBell() {
    if (!bellPanel || document.hidden) return;
    try {
        const response = await fetch(bellPanel.dataset.feedUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const feed = await response.json();
        const known = new Set([...bellList.querySelectorAll('[data-notification-id]')].map((item) => item.dataset.notificationId));
        if (!feed.items.some((item) => !known.has(item.id))) return;
        const date = (value) => new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        bellList.innerHTML = feed.items.map((item) => `<a class="bell-item" href="${escapeHtml(item.url)}" data-notification-id="${escapeHtml(item.id)}"><span class="bell-icon ${item.kind === 'complaint' ? 'is-complaint' : ''}"><i data-lucide="${item.kind === 'complaint' ? 'message-square-text' : 'receipt-text'}" class="icon"></i></span><span><span class="bell-title">${escapeHtml(item.title)}</span><span class="bell-detail">${escapeHtml(item.detail)}</span><span class="bell-date">${date(item.date)}</span></span></a>`).join('');
        renderIcons();
        if (syncBell() && !reducedMotion) { bellButton.classList.remove('has-new'); void bellButton.offsetWidth; bellButton.classList.add('has-new'); }
    } catch { /* jaringan sedang tidak tersedia */ }
}
if (bellPanel) {
    if (syncBell() && !reducedMotion) bellButton.classList.add('has-new');
    bellPanel.addEventListener('beforetoggle', (event) => { if (event.newState === 'open') placeBelow(bellPanel, bellButton); });
    bellPanel.addEventListener('toggle', (event) => { if (event.newState === 'closed') markAllRead(); });
    document.querySelector('[data-bell-read]')?.addEventListener('click', markAllRead);
    setInterval(refreshBell, 60000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshBell(); });
}

/* ---------- Pencarian cepat: tekan / ---------- */
document.addEventListener('keydown', (event) => {
    const typing = event.target.closest?.('input, textarea, select, [contenteditable]');
    const search = document.querySelector('[data-search]');
    if (event.key === '/' && !typing && search && !document.querySelector('dialog[open]')) {
        event.preventDefault();
        search.focus();
        search.select();
    }
});

/* ---------- Formulir umum ---------- */
document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => {
    const label = button.querySelector('[data-copy-label]');
    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        if (label) { label.textContent = 'Tersalin'; setTimeout(() => { label.textContent = 'Salin nomor'; }, 2000); }
    } catch { window.prompt('Salin nomor tiket ini:', button.dataset.copy); }
}));
document.querySelectorAll('[data-auto-submit]').forEach((select) => select.addEventListener('change', () => select.form.requestSubmit()));
document.querySelectorAll('[data-toast]').forEach((toast) => {
    const close = () => { toast.classList.add('is-leaving'); toast.addEventListener('animationend', () => toast.remove(), { once: true }); };
    toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
    setTimeout(close, 6000);
});
document.querySelectorAll('[data-toggle-password]').forEach((button) => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.togglePassword);
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    button.innerHTML = `<i data-lucide="${visible ? 'eye-off' : 'eye'}" class="icon" aria-hidden="true"></i>`;
    renderIcons();
}));
const capsHint = document.querySelector('[data-caps-warning]');
document.querySelectorAll('[data-caps-check]').forEach((input) => {
    const check = (event) => { if (capsHint && event.getModifierState) capsHint.hidden = !event.getModifierState('CapsLock'); };
    input.addEventListener('keydown', check);
    input.addEventListener('keyup', check);
    input.addEventListener('blur', () => { if (capsHint) capsHint.hidden = true; });
});
document.querySelectorAll('[data-money-preview]').forEach((input) => {
    const output = input.closest('.field')?.querySelector('[data-money-output]');
    const update = () => { if (output) output.textContent = input.value !== '' && Number(input.value) > 0 ? `= ${rupiah(Number(input.value), Number(input.value) % 1 ? 2 : 0)}` : ''; };
    input.addEventListener('input', update);
    update();
});
document.querySelectorAll('[data-period-toggle]').forEach((select) => {
    const sync = () => {
        select.form.querySelectorAll('[data-period]').forEach((field) => {
            const active = field.dataset.period === select.value;
            field.hidden = !active;
            field.querySelector('select').disabled = !active;
        });
    };
    select.addEventListener('change', sync);
    sync();
});

const fileSize = (bytes) => (bytes < 1024 ? `${bytes} byte` : bytes < 1048576 ? `${Math.round(bytes / 1024).toLocaleString('id-ID')} KB` : `${(bytes / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 })} MB`);
document.querySelectorAll('[data-dropzone]').forEach((zone) => {
    const input = zone.querySelector('input[type=file]');
    const label = zone.querySelector('[data-dropzone-label]');
    const original = label.innerHTML;
    const showFile = () => {
        const file = input.files?.[0];
        zone.classList.toggle('has-file', Boolean(file));
        label.innerHTML = original;
        if (file) label.textContent = `${file.name} · ${fileSize(file.size)}`;
    };
    ['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (event) => { event.preventDefault(); zone.classList.add('is-dragover'); }));
    ['dragleave', 'dragend'].forEach((type) => zone.addEventListener(type, () => zone.classList.remove('is-dragover')));
    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('is-dragover');
        if (event.dataTransfer?.files.length) { input.files = event.dataTransfer.files; showFile(); }
    });
    input.addEventListener('change', showFile);
});

/* ---------- Dialog ---------- */
document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const rect = dialog.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
}));
document.querySelectorAll('[data-dialog-open]').forEach((button) => button.addEventListener('click', () => document.getElementById(button.dataset.dialogOpen)?.showModal()));

const budgetDialog = document.getElementById('budget-dialog');
const budgetForm = document.getElementById('budget-form');
const budgetFields = ['year', 'type', 'category', 'sub_category', 'description', 'volume', 'amount', 'funding_source', 'source', 'source_date', 'verification_status'];
const refreshPreviews = (form) => form?.querySelectorAll('[data-money-preview]').forEach((input) => input.dispatchEvent(new Event('input')));
document.querySelector('[data-budget-open]')?.addEventListener('click', () => {
    const year = budgetForm.elements.year.value;
    budgetForm.reset();
    budgetForm.action = budgetForm.dataset.storeUrl;
    budgetForm.elements._method.disabled = true;
    budgetForm.elements._editing_id.value = '';
    budgetFields.filter((field) => !['type', 'verification_status'].includes(field)).forEach((field) => { budgetForm.elements[field].value = ''; });
    budgetForm.elements.year.value = year;
    budgetForm.elements.source.value = budgetForm.dataset.defaultSource ?? '';
    budgetDialog.querySelector('[data-dialog-title]').textContent = 'Tambah anggaran';
    refreshPreviews(budgetForm);
    budgetDialog.showModal();
});
document.querySelectorAll('[data-budget-edit]').forEach((button) => button.addEventListener('click', () => {
    const budget = JSON.parse(button.dataset.budgetEdit);
    budgetForm.action = button.dataset.updateUrl;
    budgetForm.elements._method.disabled = false;
    budgetForm.elements._editing_id.value = budget.id;
    budgetFields.forEach((field) => { budgetForm.elements[field].value = budget[field] ?? ''; });
    budgetDialog.querySelector('[data-dialog-title]').textContent = 'Ubah anggaran';
    refreshPreviews(budgetForm);
    budgetDialog.showModal();
}));
if (budgetDialog?.hasAttribute('data-open-on-load')) {
    const editingId = budgetForm.elements._editing_id.value;
    if (editingId) {
        budgetForm.action = `${budgetForm.dataset.storeUrl}/${encodeURIComponent(editingId)}`;
        budgetForm.elements._method.disabled = false;
        budgetDialog.querySelector('[data-dialog-title]').textContent = 'Ubah anggaran';
    }
    budgetDialog.showModal();
}

/* Dialog ubah informasi sumber dataset pada Riwayat Dataset. */
const datasetDialog = document.getElementById('dataset-dialog');
const datasetForm = datasetDialog?.querySelector('[data-dataset-form]');
document.querySelectorAll('[data-dataset-edit]').forEach((button) => button.addEventListener('click', () => {
    const dataset = JSON.parse(button.dataset.datasetEdit);
    datasetForm.action = button.dataset.updateUrl;
    datasetForm.elements.namedItem('_dataset_id').value = dataset.id;
    ['name', 'source', 'source_date'].forEach((field) => { datasetForm.elements.namedItem(field).value = dataset[field] ?? ''; });
    datasetForm.elements.namedItem('apply_to_lines').checked = true;
    datasetDialog.querySelector('[data-dataset-rows]').textContent = `${dataset.rows} baris`;
    datasetDialog.querySelector('[data-dataset-error]')?.remove();
    datasetDialog.showModal();
}));
if (datasetDialog?.hasAttribute('data-open-on-load')) datasetDialog.showModal();

/* Dialog tanggapi pengaduan di halaman Pengaduan Masuk. */
const complaintDialog = document.getElementById('complaint-dialog');
const complaintForm = complaintDialog?.querySelector('[data-complaint-form]');
document.querySelectorAll('[data-complaint-open]').forEach((button) => button.addEventListener('click', () => {
    const item = JSON.parse(button.dataset.complaintOpen);
    complaintForm.action = item.url;
    complaintForm.elements.namedItem('_complaint_id').value = item.id;
    complaintForm.elements.namedItem('status').value = item.status;
    complaintForm.elements.namedItem('response').value = item.response ?? '';
    complaintDialog.querySelectorAll('[data-field]').forEach((field) => {
        const value = item[field.dataset.field];
        field.textContent = value ?? (field.dataset.field === 'activity' ? '–' : '');
    });
    complaintDialog.querySelector('.alert')?.remove();
    complaintDialog.showModal();
}));
if (complaintDialog?.hasAttribute('data-open-on-load')) complaintDialog.showModal();

/* Pilih beberapa baris anggaran lalu tetapkan status verifikasinya sekaligus. */
const bulkForm = document.querySelector('[data-bulk-bar]');
if (bulkForm) {
    const boxes = [...document.querySelectorAll(`input[form="${bulkForm.id}"][name="ids[]"]`)];
    const selectAll = bulkForm.querySelector('[data-select-all]');
    const count = bulkForm.querySelector('[data-bulk-count]');
    const submit = bulkForm.querySelector('[type=submit]');
    const idleText = count.textContent;
    const sync = () => {
        const selected = boxes.filter((box) => box.checked).length;
        count.textContent = selected ? `${selected} baris dipilih` : idleText;
        submit.disabled = selected === 0;
        bulkForm.classList.toggle('has-selection', selected > 0);
        selectAll.checked = selected > 0 && selected === boxes.length;
        selectAll.indeterminate = selected > 0 && selected < boxes.length;
        selectAll.disabled = boxes.length === 0;
        boxes.forEach((box) => box.closest('tr')?.classList.toggle('is-selected', box.checked));
    };
    selectAll.addEventListener('change', () => { boxes.forEach((box) => { box.checked = selectAll.checked; }); sync(); });
    boxes.forEach((box) => box.addEventListener('change', sync));
    sync();
}

/* Saring baris tabel kecil langsung saat mengetik, misalnya Riwayat Dataset. */
document.querySelectorAll('[data-table-filter]').forEach((input) => {
    const rows = [...document.querySelectorAll(input.dataset.tableFilter)];
    const empty = input.dataset.emptyTarget ? document.querySelector(input.dataset.emptyTarget) : null;
    input.addEventListener('input', () => {
        const term = input.value.trim().toLocaleLowerCase('id');
        let shown = 0;
        rows.forEach((row) => {
            const match = term === '' || row.textContent.toLocaleLowerCase('id').includes(term);
            row.hidden = !match;
            if (match) shown++;
        });
        if (empty) empty.hidden = shown > 0 || rows.length === 0;
    });
});

const confirmDialog = document.getElementById('confirm-dialog');
let pendingForm = null;
document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
    if (!confirmDialog || form.dataset.confirmed) return;
    event.preventDefault();
    pendingForm = form;
    confirmDialog.querySelector('[data-confirm-title]').textContent = form.dataset.confirmTitle ?? 'Yakin ingin menghapus?';
    confirmDialog.querySelector('[data-confirm-text]').textContent = form.dataset.confirm;
    confirmDialog.querySelector('[data-confirm-yes]').textContent = form.dataset.confirmButton ?? 'Ya, hapus';
    confirmDialog.showModal();
}));
confirmDialog?.querySelector('[data-confirm-yes]')?.addEventListener('click', (event) => {
    if (!pendingForm) return;
    event.currentTarget.disabled = true;
    event.currentTarget.setAttribute('aria-busy', 'true');
    pendingForm.dataset.confirmed = 'true';
    pendingForm.submit();
});

/* Cegah klik ganda saat formulir POST dikirim. */
document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => {
    if (event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
    const button = event.submitter;
    if (button) { button.setAttribute('aria-busy', 'true'); setTimeout(() => { button.disabled = true; }, 0); }
}));
document.querySelector('[data-error-summary]')?.focus();
