import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js';
import {
    createIcons,
    ArrowBigUp, ArrowLeft, ArrowRight, CalendarX2, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, CircleCheck,
    Database, Download, Eye, EyeOff, FileDown, FileSpreadsheet, FileUp, Files, FlaskConical, FolderOpen, History, Info,
    Landmark, LayoutDashboard, Lock, LogIn, LogOut, Menu, PanelLeftClose, PanelLeftOpen, Pencil, PencilRuler, Plus,
    Search, SearchX, ShieldCheck, SlidersHorizontal, Trash2, Upload, UserRound, Wallet, X,
} from 'lucide';

const icons = {
    ArrowBigUp, ArrowLeft, ArrowRight, CalendarX2, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, CircleCheck,
    Database, Download, Eye, EyeOff, FileDown, FileSpreadsheet, FileUp, Files, FlaskConical, FolderOpen, History, Info,
    Landmark, LayoutDashboard, Lock, LogIn, LogOut, Menu, PanelLeftClose, PanelLeftOpen, Pencil, PencilRuler, Plus,
    Search, SearchX, ShieldCheck, SlidersHorizontal, Trash2, Upload, UserRound, Wallet, X,
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


document.querySelectorAll('[data-auto-submit]').forEach((select) => select.addEventListener('change', () => select.form.requestSubmit()));

document.getElementById('profile-menu')?.addEventListener('toggle', (event) => {
    document.querySelector('[data-profile-trigger]')?.setAttribute('aria-expanded', String(event.newState === 'open'));
});

/* ---------- Sidebar ---------- */
const shortcutHint = document.querySelector('[data-shortcut-hint]');
if (shortcutHint && !/Mac|iPhone|iPad/.test(navigator.platform)) shortcutHint.textContent = 'Ctrl K';

const sidebarToggles = [...document.querySelectorAll('[data-sidebar-toggle]')];
const sidebar = document.getElementById('sidebar');
const appMain = document.querySelector('.app-main');
const desktopSidebar = window.matchMedia('(min-width: 961px)');
const navGroupToggle = document.querySelector('[data-nav-group-toggle]');
const dashboardSubmenu = document.getElementById('dashboard-submenu');
const dashboardFlyout = document.getElementById('dashboard-flyout');
const sidebarSearch = document.getElementById('sidebar-search');
let dashboardGroupExpanded = navGroupToggle?.getAttribute('aria-expanded') !== 'false';

function sidebarIsRail() {
    return desktopSidebar.matches && document.documentElement.classList.contains('sidebar-collapsed');
}

function flyoutIsOpen() {
    return dashboardFlyout?.matches(':popover-open') ?? false;
}

function syncDashboardGroup() {
    if (!navGroupToggle || !dashboardSubmenu) return;
    const rail = sidebarIsRail();
    dashboardSubmenu.hidden = rail || !dashboardGroupExpanded;
    navGroupToggle.setAttribute('aria-controls', rail ? 'dashboard-flyout' : 'dashboard-submenu');
    navGroupToggle.setAttribute('aria-expanded', String(rail ? flyoutIsOpen() : dashboardGroupExpanded));
}

function closeDashboardFlyout(restoreFocus = false) {
    if (!flyoutIsOpen()) return;
    const focusInside = dashboardFlyout.contains(document.activeElement);
    dashboardFlyout.hidePopover();
    syncDashboardGroup();
    if (restoreFocus && focusInside) navGroupToggle?.focus();
}

function positionDashboardFlyout() {
    if (!dashboardFlyout || !navGroupToggle) return;
    const trigger = navGroupToggle.getBoundingClientRect();
    const width = dashboardFlyout.offsetWidth || 228;
    const height = dashboardFlyout.offsetHeight || 190;
    const left = Math.max(8, Math.min(trigger.right + 14, window.innerWidth - width - 12));
    const top = Math.max(12, Math.min(trigger.top - 4, window.innerHeight - height - 12));
    dashboardFlyout.style.left = `${left}px`;
    dashboardFlyout.style.top = `${top}px`;
    dashboardFlyout.style.right = 'auto';
    dashboardFlyout.style.bottom = 'auto';
    dashboardFlyout.style.maxHeight = `${Math.max(120, window.innerHeight - 24)}px`;
}

function openDashboardFlyout() {
    if (!dashboardFlyout || !sidebarIsRail()) return;
    positionDashboardFlyout();
    dashboardFlyout.showPopover({ source: navGroupToggle });
    positionDashboardFlyout();
    syncDashboardGroup();
    dashboardFlyout.querySelector('a')?.focus();
}

function sidebarFocusReturn() {
    return sidebarToggles.find((button) => !sidebar?.contains(button)) || sidebarToggles[0];
}

function sidebarFocusableElements() {
    return [...sidebar.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]')]
        .filter((element) => element.getClientRects().length && !element.closest('[hidden]'));
}

function syncSidebar() {
    if (!sidebar || !appMain) return;
    const desktop = desktopSidebar.matches;
    const rail = sidebarIsRail();
    const mobileOpen = document.body.classList.contains('sidebar-open');
    const visible = desktop || mobileOpen;
    const focusedInSidebar = sidebar.contains(document.activeElement);
    const focusedInFlyout = dashboardFlyout?.contains(document.activeElement);
    sidebar.inert = !visible;
    appMain.inert = !desktop && mobileOpen;
    sidebar.setAttribute('aria-hidden', String(!visible));
    if (!visible && focusedInSidebar) sidebarFocusReturn()?.focus();
    if (rail && dashboardSubmenu?.contains(document.activeElement)) navGroupToggle?.focus();
    if (!rail && focusedInFlyout) navGroupToggle?.focus();
    for (const button of sidebarToggles) {
        const expanded = desktop ? !rail : mobileOpen;
        const label = desktop ? (rail ? 'Perluas sidebar' : 'Ringkas sidebar') : (mobileOpen ? 'Tutup navigasi' : 'Buka navigasi');
        button.setAttribute('aria-expanded', String(expanded));
        button.setAttribute('aria-label', label);
        button.title = label;
        const icon = desktop
            ? (button.hasAttribute('data-sidebar-edge') ? (rail ? 'chevron-right' : 'chevron-left') : (rail ? 'panel-left-open' : 'panel-left-close'))
            : (mobileOpen ? 'x' : 'menu');
        button.innerHTML = `<i data-lucide="${icon}" class="icon" aria-hidden="true"></i>`;
    }
    syncDashboardGroup();
    renderIcons();
}

function setDesktopSidebarCollapsed(collapsed) {
    closeDashboardFlyout(true);
    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
    try { localStorage.setItem('nibung.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch {}
    syncSidebar();
}

function toggleMobileSidebar(open) {
    closeDashboardFlyout();
    document.body.classList.toggle('sidebar-open', open);
    syncSidebar();
    if (open) sidebarFocusableElements()[0]?.focus(); else sidebarFocusReturn()?.focus();
}

function focusSidebarSearch() {
    if (!sidebarSearch || document.querySelector('dialog[open]')) return;
    if (desktopSidebar.matches) setDesktopSidebarCollapsed(false);
    else toggleMobileSidebar(true);
    sidebarSearch.focus();
    sidebarSearch.select();
}

sidebarToggles.forEach((button) => button.addEventListener('click', () => {
    if (desktopSidebar.matches) setDesktopSidebarCollapsed(!sidebarIsRail());
    else toggleMobileSidebar(!document.body.classList.contains('sidebar-open'));
}));
document.querySelectorAll('[data-sidebar-close]').forEach((element) => element.addEventListener('click', () => toggleMobileSidebar(false)));
document.querySelector('[data-sidebar-search-open]')?.addEventListener('click', focusSidebarSearch);
navGroupToggle?.addEventListener('click', () => {
    if (sidebarIsRail()) {
        if (flyoutIsOpen()) closeDashboardFlyout(true); else openDashboardFlyout();
    } else {
        dashboardGroupExpanded = !dashboardGroupExpanded;
        syncDashboardGroup();
    }
});
navGroupToggle?.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowRight') {
        event.preventDefault();
        if (sidebarIsRail()) openDashboardFlyout();
        else { dashboardGroupExpanded = true; syncDashboardGroup(); dashboardSubmenu?.querySelector('a')?.focus(); }
    }
    if (event.key === 'ArrowLeft' && !sidebarIsRail()) {
        event.preventDefault();
        dashboardGroupExpanded = false;
        syncDashboardGroup();
    }
});
dashboardFlyout?.addEventListener('toggle', syncDashboardGroup);
dashboardFlyout?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeDashboardFlyout()));
document.addEventListener('keydown', (event) => {
    const typing = event.target.closest?.('input, textarea, select, [contenteditable]');
    const shortcut = ((event.metaKey || event.ctrlKey) && !event.altKey && event.key.toLowerCase() === 'k') || (event.key === '/' && !typing);
    if (shortcut && !event.isComposing && !document.querySelector('dialog[open]') && sidebarSearch) {
        event.preventDefault();
        focusSidebarSearch();
        return;
    }
    if (event.key === 'Escape' && flyoutIsOpen()) {
        event.preventDefault();
        closeDashboardFlyout(true);
        return;
    }
    if (desktopSidebar.matches || !document.body.classList.contains('sidebar-open')) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        toggleMobileSidebar(false);
    }
    if (event.key === 'Tab') {
        const focusable = sidebarFocusableElements();
        const first = focusable[0], last = focusable.at(-1);
        if (!first) return;
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
});
desktopSidebar.addEventListener('change', () => {
    closeDashboardFlyout(true);
    document.body.classList.remove('sidebar-open');
    syncSidebar();
});
window.addEventListener('resize', () => {
    if (flyoutIsOpen()) positionDashboardFlyout();
});
sidebar?.addEventListener('scroll', () => closeDashboardFlyout(true), { passive: true });
window.addEventListener('pagehide', () => closeDashboardFlyout());
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
