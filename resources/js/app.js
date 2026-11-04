import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js';
import { createIcons, ArrowDownLeft, ArrowUpRight, ArrowRight, BadgeCheck, BookOpen, CalendarDays, ChartNoAxesCombined, Check, ChevronDown, ChevronLeft, ChevronRight, Command, ChevronsUpDown, CircleAlert, CircleCheck, CircleHelp, Clock3, Database, Download, Eye, EyeOff, FileQuestion, FileSpreadsheet, Files, FileUp, Fingerprint, FlaskConical, FolderOpen, History, Info, Landmark, Layers, LayoutDashboard, ListFilter, LockKeyhole, LogIn, LogOut, MapPin, Menu, Pencil, Plus, ScanLine, Search, SearchX, ShieldCheck, SlidersHorizontal, Sprout, Trash2, Upload, Wallet, MousePointer2, PanelLeftClose, PanelLeftOpen, X, Mail, ArrowLeft, ArrowBigUp } from 'lucide';

const icons = { ArrowDownLeft, ArrowUpRight, ArrowRight, BadgeCheck, BookOpen, CalendarDays, ChartNoAxesCombined, Check, ChevronDown, ChevronLeft, ChevronRight, Command, ChevronsUpDown, CircleAlert, CircleCheck, CircleHelp, Clock3, Database, Download, Eye, EyeOff, FileQuestion, FileSpreadsheet, Files, FileUp, Fingerprint, FlaskConical, FolderOpen, History, Info, Landmark, Layers, LayoutDashboard, ListFilter, LockKeyhole, LogIn, LogOut, MapPin, Menu, Pencil, Plus, ScanLine, Search, SearchX, ShieldCheck, SlidersHorizontal, Sprout, Trash2, Upload, Wallet, MousePointer2, PanelLeftClose, PanelLeftOpen, X, Mail, ArrowLeft, ArrowBigUp };
createIcons({ icons });

const rupiah = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
Chart.register(DoughnutController, ArcElement, Tooltip);
Chart.defaults.font.family = 'Plus Jakarta Sans Variable, sans-serif';
Chart.defaults.color = '#8b967f';
Chart.defaults.animation.duration = reducedMotion ? 0 : 650;
Chart.defaults.plugins.legend = { display: false };
const tooltipOptions = {
    backgroundColor: '#193e30', titleColor: '#fff', bodyColor: '#c7d4bc', padding: 13,
    cornerRadius: 8, displayColors: false, titleFont: { size: 10, weight: 500 },
    bodyFont: { size: 11, weight: 600 }, callbacks: { label: (context) => rupiah(context.raw) },
};

function renderCharts() {
    const data = window.dashboardData;
    if (!data) return;
    const incomeCanvas = document.getElementById('income-chart');
    if (incomeCanvas) {
        new Chart(incomeCanvas, {
            type: 'doughnut',
            data: { labels: data.income.map((row) => row.name), datasets: [{ data: data.income.map((row) => Number(row.amount)), backgroundColor: data.income.map((row) => row.color), borderWidth: 0, hoverOffset: 5, borderRadius: 10, spacing: 5 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '78%', layout: { padding: 4 }, animation: reducedMotion ? false : { duration: 1100, easing: 'easeOutQuart', animateRotate: true }, plugins: { legend: { display: false }, tooltip: tooltipOptions } },
        });
    }

}

if (document.fonts?.ready) document.fonts.ready.then(renderCharts); else renderCharts();

document.querySelectorAll('[data-auto-submit]').forEach((select) => select.addEventListener('change', () => select.form.requestSubmit()));
const dismissFlash = (flash) => { flash.classList.add('is-leaving'); flash.addEventListener('animationend', () => flash.remove(), { once: true }); };
document.querySelectorAll('[data-dismiss]').forEach((button) => button.addEventListener('click', () => dismissFlash(button.closest('.flash'))));
document.querySelectorAll('.flash-success').forEach((flash) => setTimeout(() => flash.isConnected && dismissFlash(flash), 6000));
document.getElementById('profile-menu')?.addEventListener('toggle', (event) => {
    document.querySelector('[data-profile-trigger]').setAttribute('aria-expanded', String(event.newState === 'open'));
});
document.querySelectorAll('[data-toggle-password]').forEach((button) => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.togglePassword);
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    button.innerHTML = `<i data-lucide="${visible ? 'eye-off' : 'eye'}" class="icon" aria-hidden="true"></i>`;
    createIcons({ icons });
}));

const sidebarToggles = [...document.querySelectorAll('[data-sidebar-toggle]')];
const sidebar = document.getElementById('sidebar');
const appMain = document.querySelector('.app-main');
const desktopSidebar = window.matchMedia('(min-width: 681px)');
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
    createIcons({ icons });
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
document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => toggleMobileSidebar(false));
document.querySelector('[data-sidebar-dismiss]')?.addEventListener('click', () => toggleMobileSidebar(false));
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

const budgetDialog = document.getElementById('budget-dialog');
const budgetForm = document.getElementById('budget-form');
const dialogTitle = budgetDialog?.querySelector('[data-dialog-title]');
const fields = ['year', 'type', 'category', 'description', 'amount', 'realization', 'source', 'source_date', 'verification_status'];
document.querySelector('[data-budget-open]')?.addEventListener('click', () => {
    budgetForm.reset();
    budgetForm.action = budgetForm.dataset.storeUrl;
    budgetForm.elements._method.disabled = true;
    budgetForm.elements._editing_id.value = '';
    fields.filter((field) => !['year','type','verification_status'].includes(field)).forEach((field) => budgetForm.elements[field].value = '');
    budgetForm.elements.verification_status.value = 'unverified';
    dialogTitle.textContent = 'Tambah anggaran';
    budgetForm.querySelector('[data-budget-demo-note]').hidden = true;
    budgetDialog.showModal();
});
document.querySelectorAll('[data-budget-edit]').forEach((button) => button.addEventListener('click', () => {
    const budget = JSON.parse(button.dataset.budgetEdit);
    budgetForm.action = button.dataset.updateUrl;
    budgetForm.elements._method.disabled = false;
    budgetForm.elements._editing_id.value = budget.id;
    fields.forEach((field) => budgetForm.elements[field].value = budget[field] ?? '');
    dialogTitle.textContent = 'Edit anggaran';
    budgetForm.querySelector('[data-budget-demo-note]').hidden = !budget.is_demo;
    budgetDialog.showModal();
}));
document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach((dialog) => dialog.addEventListener('click', (event) => { if (event.target === dialog) { const rect = dialog.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close(); } }));
if (budgetDialog?.hasAttribute('data-open-on-load')) {
    const editingId = budgetForm.elements._editing_id.value;
    if (editingId) { budgetForm.action = `${budgetForm.dataset.storeUrl}/${encodeURIComponent(editingId)}`; budgetForm.elements._method.disabled = false; dialogTitle.textContent = 'Edit anggaran'; }
    budgetDialog.showModal();
}
const deleteDialog = document.getElementById('delete-dialog');
let deleteForm = null;
document.querySelectorAll('[data-confirm-delete]').forEach((form) => form.addEventListener('submit', (event) => {
    event.preventDefault();
    deleteForm = form;
    deleteDialog.querySelector('[data-delete-description]').textContent = form.dataset.confirmDelete;
    deleteDialog.showModal();
}));
document.querySelector('[data-delete-confirm]')?.addEventListener('click', (event) => { if (deleteForm) { event.currentTarget.disabled = true; deleteForm.submit(); } });
document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    const button = event.submitter;
    if (button && form.method.toLowerCase() === 'post') { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
}));
if (document.querySelector('[data-error-summary]') && !budgetDialog?.open) document.querySelector('[data-error-summary]').focus();

/* ---------- Animasi masuk dan angka naik ---------- */
const revealSelector = '.page-heading, .overview-banner, .demo-notice, .metric-card, .realization-summary, .charts-grid > .card, .budget-preview, .bottom-note, .budget-totals, main > section.card, .info-strip, .quality-metric, .quality-grid > .card, .quality-note, .about-intro, .about-features > .card, .about-reading, .admin-data-card, .admin-lower-grid > .card, .dataset-history';

function countUp(element) {
    if (reducedMotion || element.dataset.counted) return;
    element.dataset.counted = 'true';
    const raw = element.dataset.count;
    const decimals = raw.includes(',') ? raw.split(',')[1].length : 0;
    const target = Number(raw.replace(/\./g, '').replace(',', '.'));
    if (!Number.isFinite(target) || target === 0) return;
    const format = new Intl.NumberFormat('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    const start = performance.now() + 150;
    const tick = (now) => {
        const progress = Math.min(Math.max((now - start) / 1100, 0), 1);
        element.textContent = format.format(target * (1 - Math.pow(1 - progress, 4)));
        if (progress < 1) requestAnimationFrame(tick); else element.textContent = raw;
    };
    element.textContent = format.format(0);
    requestAnimationFrame(tick);
}

if (document.documentElement.classList.contains('js-reveal')) {
    let batch = 0;
    const observer = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            entry.target.style.setProperty('--reveal-index', Math.min(batch++, 6));
            entry.target.classList.add('is-visible');
            entry.target.querySelectorAll('[data-count]').forEach(countUp);
            observer.unobserve(entry.target);
        });
        requestAnimationFrame(() => { batch = 0; });
    }, { rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll(revealSelector).forEach((element) => observer.observe(element));
    setTimeout(() => document.querySelectorAll(revealSelector).forEach((element) => element.classList.add('is-visible')), 3000);
} else {
    document.querySelectorAll('[data-count]').forEach(countUp);
}

/* ---------- Petunjuk pintasan pencarian ---------- */
const shortcutHint = document.querySelector('[data-shortcut-hint]');
if (shortcutHint && !/Mac|iPhone|iPad/.test(navigator.platform)) shortcutHint.textContent = 'Ctrl K';

/* ---------- Kotak unggah berkas ---------- */
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

/* ---------- Peringatan Caps Lock di halaman login ---------- */
const capsWarning = document.querySelector('[data-caps-warning]');
document.querySelectorAll('[data-caps-check]').forEach((input) => {
    const check = (event) => { if (capsWarning && event.getModifierState) capsWarning.hidden = !event.getModifierState('CapsLock'); };
    input.addEventListener('keydown', check);
    input.addEventListener('keyup', check);
    input.addEventListener('blur', () => { if (capsWarning) capsWarning.hidden = true; });
});
