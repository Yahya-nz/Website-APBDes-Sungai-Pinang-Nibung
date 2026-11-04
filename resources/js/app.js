import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js';
import {
    createIcons, ArrowBigUp, ArrowDownToLine, ArrowLeft, ArrowRight, ArrowUpFromLine, CalendarX2, ChevronDown, ChevronsLeft,
    CircleAlert, CircleCheck, Database, Download, Eye, EyeOff, FileCheck2, FileDown, FileSpreadsheet, FileUp, FlaskConical,
    FolderOpen, History, Info, LayoutGrid, Lock, LogIn, LogOut, Menu, Pencil, PencilRuler, Plus, Search, SearchX, Table2,
    Trash2, Upload, UserRound, Wallet, X,
} from 'lucide';

const icons = {
    ArrowBigUp, ArrowDownToLine, ArrowLeft, ArrowRight, ArrowUpFromLine, CalendarX2, ChevronDown, ChevronsLeft, CircleAlert,
    CircleCheck, Database, Download, Eye, EyeOff, FileCheck2, FileDown, FileSpreadsheet, FileUp, FlaskConical, FolderOpen,
    History, Info, LayoutGrid, Lock, LogIn, LogOut, Menu, Pencil, PencilRuler, Plus, Search, SearchX, Table2, Trash2, Upload,
    UserRound, Wallet, X,
};
const renderIcons = () => createIcons({ icons });
renderIcons();

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

/* ---------- Muncul bertahap saat discroll ---------- */
const revealItems = [...document.querySelectorAll('[data-reveal]')];
revealItems.forEach((item, index) => item.style.setProperty('--reveal-index', Math.min(index, 6)));
if (document.documentElement.classList.contains('js-reveal')) {
    let batch = 0;
    const observer = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            entry.target.style.setProperty('--reveal-index', batch++);
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
            entry.target.querySelectorAll('[data-count]').forEach(countUp);
        });
        requestAnimationFrame(() => { batch = 0; });
    }, { rootMargin: '0px 0px -40px 0px' });
    revealItems.forEach((item) => observer.observe(item));
}

/* ---------- Angka naik dari nol ---------- */
function countUp(element) {
    if (reducedMotion || element.dataset.counted) return;
    element.dataset.counted = 'true';
    const raw = element.dataset.count;
    const decimals = raw.includes(',') ? raw.split(',')[1].length : 0;
    const target = Number(raw.replace(/\./g, '').replace(',', '.'));
    if (!Number.isFinite(target) || target === 0) return;
    const format = new Intl.NumberFormat('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    const duration = 1100;
    const start = performance.now() + 150;
    const tick = (now) => {
        const progress = Math.min(Math.max((now - start) / duration, 0), 1);
        const eased = 1 - Math.pow(1 - progress, 4);
        element.textContent = format.format(target * eased);
        if (progress < 1) requestAnimationFrame(tick); else element.textContent = raw;
    };
    element.textContent = format.format(0);
    requestAnimationFrame(tick);
}
if (!document.documentElement.classList.contains('js-reveal')) document.querySelectorAll('[data-count]').forEach(countUp);

/* ---------- Diagram pendapatan ---------- */
function renderIncomeChart() {
    const canvas = document.getElementById('income-chart');
    const income = window.dashboardData?.income;
    if (!canvas || !income?.length) return;
    Chart.register(DoughnutController, ArcElement, Tooltip);
    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: income.map((row) => row.name),
            datasets: [{ data: income.map((row) => Number(row.amount)), backgroundColor: income.map((row) => row.color), borderColor: '#fff', borderWidth: 2, hoverOffset: 6 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            layout: { padding: 6 },
            animation: reducedMotion ? false : { duration: 1000, easing: 'easeOutQuart', animateRotate: true, animateScale: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#16212b', padding: 10, cornerRadius: 6, displayColors: false,
                    titleFont: { family: 'Plus Jakarta Sans Variable', size: 12, weight: 500 },
                    bodyFont: { family: 'Plus Jakarta Sans Variable', size: 13, weight: 700 },
                    callbacks: { label: (context) => rupiah.format(context.raw) },
                },
            },
        },
    });
}
if (document.fonts?.ready) document.fonts.ready.then(renderIncomeChart); else renderIncomeChart();

/* ---------- Topbar & pencarian ---------- */
const topbar = document.querySelector('.topbar');
const syncTopbar = () => topbar?.classList.toggle('is-scrolled', window.scrollY > 4);
window.addEventListener('scroll', syncTopbar, { passive: true });
syncTopbar();

const globalSearch = document.querySelector('[data-global-search]');
document.addEventListener('keydown', (event) => {
    const typing = event.target.closest?.('input, textarea, select, [contenteditable]');
    if (event.key === '/' && !typing && globalSearch && !document.querySelector('dialog[open]')) {
        event.preventDefault();
        globalSearch.focus();
        globalSearch.select();
    }
});

document.querySelectorAll('[data-auto-submit]').forEach((select) => select.addEventListener('change', () => select.form.requestSubmit()));

document.getElementById('profile-menu')?.addEventListener('toggle', (event) => {
    document.querySelector('[data-profile-trigger]')?.setAttribute('aria-expanded', String(event.newState === 'open'));
});

/* ---------- Sidebar ---------- */
const sidebar = document.getElementById('sidebar');
const appMain = document.querySelector('.app-main');
const openButton = document.querySelector('[data-sidebar-open]');
const collapseButton = document.querySelector('[data-sidebar-collapse]');
const desktop = window.matchMedia('(min-width: 961px)');

function syncSidebar() {
    if (!sidebar) return;
    const mobileOpen = document.body.classList.contains('sidebar-open');
    const collapsed = document.documentElement.classList.contains('sidebar-collapsed');
    sidebar.inert = !desktop.matches && !mobileOpen;
    if (appMain) appMain.inert = !desktop.matches && mobileOpen;
    openButton?.setAttribute('aria-expanded', String(mobileOpen));
    if (collapseButton) {
        collapseButton.setAttribute('aria-expanded', String(!collapsed));
        collapseButton.querySelector('.nav-text').textContent = collapsed ? 'Lebarkan menu' : 'Ciutkan menu';
        collapseButton.title = collapsed ? 'Lebarkan menu' : 'Ciutkan menu';
    }
}

function setMobileSidebar(open) {
    document.body.classList.toggle('sidebar-open', open);
    syncSidebar();
    if (open) sidebar.querySelector('a, button')?.focus(); else openButton?.focus();
}

openButton?.addEventListener('click', () => setMobileSidebar(true));
document.querySelectorAll('[data-sidebar-close]').forEach((element) => element.addEventListener('click', () => setMobileSidebar(false)));
collapseButton?.addEventListener('click', () => {
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('apbdes.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch {}
    syncSidebar();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) setMobileSidebar(false);
});
desktop.addEventListener('change', () => { document.body.classList.remove('sidebar-open'); syncSidebar(); });
syncSidebar();

/* ---------- Pesan sukses ---------- */
document.querySelectorAll('[data-toast]').forEach((toast) => {
    const close = () => { toast.classList.add('is-leaving'); toast.addEventListener('animationend', () => toast.remove(), { once: true }); };
    toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
    setTimeout(close, 5000);
});

/* ---------- Login ---------- */
document.querySelectorAll('[data-toggle-password]').forEach((button) => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.togglePassword);
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    button.innerHTML = `<i data-lucide="${visible ? 'eye-off' : 'eye'}" class="icon" aria-hidden="true"></i>`;
    renderIcons();
}));
const capsWarning = document.querySelector('[data-caps-warning]');
document.querySelectorAll('[data-caps-check]').forEach((input) => {
    const check = (event) => { if (capsWarning && event.getModifierState) capsWarning.hidden = !event.getModifierState('CapsLock'); };
    input.addEventListener('keydown', check);
    input.addEventListener('keyup', check);
    input.addEventListener('blur', () => { if (capsWarning) capsWarning.hidden = true; });
});

/* ---------- Impor berkas ---------- */
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

/* ---------- Formulir anggaran ---------- */
document.querySelectorAll('[data-money-preview]').forEach((input) => {
    const output = input.parentElement.querySelector('[data-money-output]');
    const update = () => { if (output) output.textContent = input.value !== '' && Number(input.value) >= 0 ? `= ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(input.value))}` : ''; };
    input.addEventListener('input', update);
    update();
});

const budgetDialog = document.getElementById('budget-dialog');
const budgetForm = document.getElementById('budget-form');
const dialogTitle = budgetDialog?.querySelector('[data-dialog-title]');
const fields = ['year', 'type', 'category', 'description', 'amount', 'realization', 'source', 'source_date', 'verification_status'];
const refreshMoneyPreview = () => budgetForm?.querySelectorAll('[data-money-preview]').forEach((input) => input.dispatchEvent(new Event('input')));

document.querySelector('[data-budget-open]')?.addEventListener('click', () => {
    const year = budgetForm.elements.year.value;
    budgetForm.reset();
    budgetForm.action = budgetForm.dataset.storeUrl;
    budgetForm.elements._method.disabled = true;
    budgetForm.elements._editing_id.value = '';
    fields.filter((field) => !['year', 'type', 'verification_status'].includes(field)).forEach((field) => { budgetForm.elements[field].value = ''; });
    budgetForm.elements.year.value = year;
    budgetForm.elements.verification_status.value = 'unverified';
    dialogTitle.textContent = 'Tambah baris anggaran';
    budgetForm.querySelector('[data-budget-demo-note]').hidden = true;
    refreshMoneyPreview();
    budgetDialog.showModal();
});
document.querySelectorAll('[data-budget-edit]').forEach((button) => button.addEventListener('click', () => {
    const budget = JSON.parse(button.dataset.budgetEdit);
    budgetForm.action = button.dataset.updateUrl;
    budgetForm.elements._method.disabled = false;
    budgetForm.elements._editing_id.value = budget.id;
    fields.forEach((field) => { budgetForm.elements[field].value = budget[field] ?? ''; });
    dialogTitle.textContent = 'Ubah baris anggaran';
    budgetForm.querySelector('[data-budget-demo-note]').hidden = !budget.is_demo;
    refreshMoneyPreview();
    budgetDialog.showModal();
}));
document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const rect = dialog.getBoundingClientRect();
    const outside = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
    if (outside) dialog.close();
}));
if (budgetDialog?.hasAttribute('data-open-on-load')) {
    const editingId = budgetForm.elements._editing_id.value;
    if (editingId) {
        budgetForm.action = `${budgetForm.dataset.storeUrl}/${encodeURIComponent(editingId)}`;
        budgetForm.elements._method.disabled = false;
        dialogTitle.textContent = 'Ubah baris anggaran';
    }
    budgetDialog.showModal();
}

const deleteDialog = document.getElementById('delete-dialog');
let pendingDeleteForm = null;
document.querySelectorAll('[data-confirm-delete]').forEach((form) => form.addEventListener('submit', (event) => {
    event.preventDefault();
    pendingDeleteForm = form;
    deleteDialog.querySelector('[data-delete-description]').textContent = form.dataset.confirmDelete;
    deleteDialog.showModal();
}));
document.querySelector('[data-delete-confirm]')?.addEventListener('click', (event) => {
    if (!pendingDeleteForm) return;
    event.currentTarget.disabled = true;
    event.currentTarget.setAttribute('aria-busy', 'true');
    pendingDeleteForm.submit();
});

/* Cegah klik ganda saat formulir POST dikirim. */
document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => {
    if (event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
    const button = event.submitter;
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
}));

const errorSummary = document.querySelector('[data-error-summary]');
if (errorSummary && !budgetDialog?.open) errorSummary.focus();
