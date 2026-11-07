import './bootstrap';
import {
    Accessibility,
    AlertCircle,
    ArrowUp,
    ArrowUpRight,
    ArrowLeft,
    ArrowRight,
    BookMarked,
    BookOpen,
    BookOpenCheck,
    Calendar,
    Check,
    CheckCircle2,
    ChevronRight,
    Clapperboard,
    Clock3,
    Clock,
    Copy,
    createIcons,
    Download,
    Edit3,
    Eye,
    ExternalLink,
    FilePlus2,
    FileQuestion,
    Files,
    Filter,
    FolderCog,
    Image,
    Layers,
    LayoutDashboard,
    ListChecks,
    LockKeyhole,
    LogIn,
    LogOut,
    Menu,
    NotebookTabs,
    Palette,
    PanelLeft,
    Plus,
    PlusCircle,
    PlayCircle,
    RefreshCw,
    RotateCcw,
    Save,
    Search,
    Settings2,
    Sparkles,
    Square,
    Star,
    StarOff,
    Trash2,
    Target,
    Upload,
    X,
} from 'lucide';

const icons = {
    Accessibility,
    AlertCircle,
    ArrowUp,
    ArrowUpRight,
    ArrowLeft,
    ArrowRight,
    BookMarked,
    BookOpen,
    BookOpenCheck,
    Calendar,
    Check,
    CheckCircle2,
    ChevronRight,
    Clock,
    Clapperboard,
    Clock3,
    Copy,
    Edit3,
    Download,
    Download,
    Eye,
    ExternalLink,
    FilePlus2,
    FileQuestion,
    Files,
    Filter,
    FolderCog,
    Image,
    Layers,
    LayoutDashboard,
    ListChecks,
    LockKeyhole,
    LogIn,
    LogOut,
    Menu,
    NotebookTabs,
    Palette,
    PanelLeft,
    Plus,
    PlusCircle,
    PlayCircle,
    RefreshCw,
    RotateCcw,
    Save,
    Search,
    Settings2,
    Sparkles,
    Square,
    Star,
    StarOff,
    Trash2,
    Target,
    Upload,
    X,
};

const settingsKey = 'catatin-ui-settings';
const accountId = document.documentElement.dataset.accountId || 'guest';
const accountSettingsKey = `${settingsKey}:${accountId}`;
const defaultSettings = { accent: '#2F6F64', navigationFooter: '#1F2937', reduceMotion: false };

function readSettings() {
    try {
        const stored = JSON.parse(localStorage.getItem(accountSettingsKey) || '{}');
        return {
            ...defaultSettings,
            ...stored,
            accent: document.documentElement.dataset.themeAccent || stored.accent || defaultSettings.accent,
            navigationFooter: document.documentElement.dataset.themeNavSurface || stored.navigationFooter || defaultSettings.navigationFooter,
        };
    } catch {
        return {
            ...defaultSettings,
            accent: document.documentElement.dataset.themeAccent || defaultSettings.accent,
            navigationFooter: document.documentElement.dataset.themeNavSurface || defaultSettings.navigationFooter,
        };
    }
}

function applySettings(settings) {
    const accent = /^#[\da-f]{6}$/i.test(settings.accent) ? settings.accent : defaultSettings.accent;
    const navigationFooter = /^#[\da-f]{6}$/i.test(settings.navigationFooter) ? settings.navigationFooter : defaultSettings.navigationFooter;
    const channels = accent.slice(1).match(/[\da-f]{2}/gi).map((channel) => parseInt(channel, 16));
    const hover = channels.map((channel) => Math.round(channel * .78));
    const light = channels.map((channel) => Math.round(channel + (255 - channel) * .28));

    document.documentElement.style.setProperty('--theme-accent', accent);
    document.documentElement.style.setProperty('--theme-nav-surface', navigationFooter);
    document.documentElement.style.setProperty('--theme-accent-hover', `rgb(${hover.join(', ')})`);
    document.documentElement.style.setProperty('--theme-accent-light', `rgb(${light.join(', ')})`);
    document.documentElement.style.setProperty('--theme-accent-rgb', channels.join(', '));
    document.documentElement.classList.toggle('reduce-motion', Boolean(settings.reduceMotion));
}

function saveSettings(settings) {
    try {
        localStorage.setItem(accountSettingsKey, JSON.stringify(settings));
    } catch {
        // The current page still receives the chosen theme if storage is unavailable.
    }

    applySettings(settings);
}

applySettings(readSettings());

try {
    if (window.location.pathname !== '/login') {
        sessionStorage.setItem('catatin-last-page', `${window.location.pathname}${window.location.search}`);
    }
} catch {
    // Login modal can fall back to the dashboard if session storage is unavailable.
}

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });

    const dismissNotification = (notification, immediate = false) => {
        if (!notification || notification.dataset.dismissed === 'true') return;
        notification.dataset.dismissed = 'true';
        const remove = () => notification.remove();
        if (immediate
            || document.documentElement.classList.contains('reduce-motion')
            || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            remove();
            return;
        }
        notification.classList.add('notification-exit');
        notification.addEventListener('animationend', remove, { once: true });
        window.setTimeout(remove, 250);
    };

    document.querySelectorAll('[data-notification]').forEach((notification) => {
        const delay = Number(notification.dataset.autoDismiss || 4500);
        window.setTimeout(() => dismissNotification(notification), delay);
    });

    document.querySelectorAll('[data-dismiss-notification]').forEach((button) => {
        button.addEventListener('click', () => {
            dismissNotification(document.getElementById(button.dataset.dismissNotification), false);
        });
    });

    const notificationStack = document.querySelector('.notification-stack');
    let toastSequence = 0;
    const showAjaxToast = (type, message) => {
        if (!notificationStack || !message) return;
        const duplicate = [...notificationStack.querySelectorAll('[data-ajax-toast]')]
            .find((toast) => toast.dataset.toastType === type && toast.dataset.toastMessage === message);
        if (duplicate) return;

        const notification = document.createElement('div');
        const id = `ajax-notification-${++toastSequence}`;
        notification.id = id;
        notification.dataset.notification = type;
        notification.dataset.ajaxToast = 'true';
        notification.dataset.toastType = type;
        notification.dataset.toastMessage = message;
        notification.dataset.autoDismiss = type === 'success' ? '4500' : '6500';
        notification.className = `notification-enter pointer-events-auto flex w-full items-center justify-between rounded-xl border p-3.5 text-sm shadow-lg backdrop-blur ${
            type === 'success'
                ? 'border-emerald-200 bg-white/95 text-emerald-800 shadow-slate-900/10'
                : 'border-rose-200 bg-white/95 text-rose-800 shadow-slate-900/10'
        }`;
        notification.setAttribute('role', type === 'success' ? 'status' : 'alert');

        const content = document.createElement('div');
        content.className = 'flex items-center gap-3';
        const icon = document.createElement('div');
        icon.className = `flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${
            type === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'
        }`;
        const iconElement = document.createElement('i');
        iconElement.dataset.lucide = type === 'success' ? 'check-circle-2' : 'alert-circle';
        iconElement.className = 'h-5 w-5';
        icon.append(iconElement);
        const text = document.createElement('div');
        const heading = document.createElement('p');
        heading.className = type === 'success' ? 'font-semibold text-emerald-900' : 'font-semibold text-rose-900';
        heading.textContent = type === 'success' ? 'Berhasil!' : 'Terjadi Kesalahan!';
        const description = document.createElement('p');
        description.className = type === 'success' ? 'text-xs text-emerald-700' : 'text-xs text-rose-700';
        description.textContent = message;
        text.append(heading, description);
        content.append(icon, text);

        const close = document.createElement('button');
        close.type = 'button';
        close.dataset.dismissNotification = id;
        close.className = `ml-3 shrink-0 rounded-md p-1 ${
            type === 'success' ? 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700' : 'text-rose-500 hover:bg-rose-50 hover:text-rose-700'
        }`;
        close.setAttribute('aria-label', 'Tutup notifikasi');
        const closeIcon = document.createElement('i');
        closeIcon.dataset.lucide = 'x';
        closeIcon.className = 'h-4 w-4';
        close.append(closeIcon);
        notification.append(content, close);
        notificationStack.append(notification);
        createIcons({ icons });
        window.setTimeout(() => dismissNotification(notification), Number(notification.dataset.autoDismiss));
        close.addEventListener('click', () => dismissNotification(notification));
    };

    const showFormErrors = (errors) => {
        const messages = Object.values(errors || {}).flat().filter(Boolean);
        const message = messages.join(' ');
        showAjaxToast('error', message || 'Periksa kembali data yang dimasukkan.');
    };

    const updateGoalProgress = (form, data) => {
        if (!data || !form.closest('[data-goal-progress]')) return;
        const panel = form.closest('[data-goal-progress]');
        const percentage = Number(data.percentage || 0);
        panel.dataset.goalRemainingValue = Number(data.remaining).toFixed(2);
        const progressBar = panel.querySelector('[data-goal-progress-bar]');
        const progressLabel = panel.querySelector('[data-goal-progress-label]');
        const progressValue = panel.querySelector('[data-goal-progress-value]');
        const remaining = panel.querySelector('[data-goal-remaining]');
        if (progressBar) progressBar.style.width = `${Math.max(0, Math.min(100, percentage))}%`;
        if (progressLabel) progressLabel.textContent = `${percentage.toFixed(1)}% tercapai`;
        if (progressValue) progressValue.textContent = `${Number(data.current_value).toFixed(2)} / ${Number(data.target_value).toFixed(2)} ${data.unit}`;
        if (remaining) remaining.textContent = `Sisa ${Number(data.remaining).toFixed(2)} ${data.unit}`;
        const quickRemaining = panel.querySelector('[data-goal-quick-remaining]');
        if (quickRemaining) quickRemaining.textContent = `Sisa target: ${Number(data.remaining).toFixed(2)} ${data.unit}`;
        const status = panel.querySelector('[data-goal-status]');
        if (status) status.textContent = data.status === 'completed' ? 'Tuntas' : 'Aktif';

        const entries = panel.querySelector('[data-goal-progress-entries]');
        const entry = data.progress_entry;
        if (entries && entry) {
            const row = document.createElement('div');
            row.className = 'flex flex-wrap items-center justify-between gap-2 py-3';
            const details = document.createElement('div');
            const amount = document.createElement('p');
            amount.className = 'font-semibold text-slate-800';
            amount.textContent = `+${Number(entry.amount).toFixed(2)} ${data.unit}`;
            details.append(amount);
            if (entry.note) {
                const note = document.createElement('p');
                note.className = 'text-sm text-slate-500';
                note.textContent = entry.note;
                details.append(note);
            }
            const timestamp = document.createElement('time');
            timestamp.className = 'text-xs text-slate-400';
            timestamp.textContent = entry.recorded_at;
            row.append(details, timestamp);
            panel.querySelector('[data-goal-progress-empty]')?.remove();
            entries.prepend(row);
            form.reset();
        }
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-ajax-form]');
        if (!form) return;
        event.preventDefault();
        if (form.dataset.ajaxSubmitting === 'true') return;

        const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type])')];
        const priorButtons = buttons.map((button) => ({
            button,
            disabled: button.disabled,
            html: button.innerHTML,
            ariaBusy: button.getAttribute('aria-busy'),
        }));
        form.dataset.ajaxSubmitting = 'true';
        let confirmedMessage = null;
        buttons.forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (button === event.submitter) button.textContent = 'Menyimpan…';
        });

        try {
            const token = form.querySelector('input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
            });
            let payload;
            try {
                payload = await response.json();
            } catch {
                payload = null;
            }

            if (!response.ok) {
                if (response.status === 422) {
                    showFormErrors(payload?.errors);
                } else if (response.status === 419) {
                    showAjaxToast('error', 'Sesi keamanan berakhir. Muat ulang halaman lalu coba kembali.');
                } else if (response.status >= 500) {
                    showAjaxToast('error', 'Server gagal menyimpan perubahan. Silakan coba kembali.');
                } else {
                    showAjaxToast('error', payload?.message || 'Permintaan gagal diproses. Silakan coba kembali.');
                }
                return;
            }
            if (!payload || typeof payload.message !== 'string') {
                showAjaxToast('error', 'Server tidak mengirim konfirmasi operasi yang valid.');
                return;
            }
            confirmedMessage = payload.message;
            if (payload.data && typeof payload.data === 'object') {
                const formValues = {
                    title: payload.data.title,
                    name: payload.data.name,
                    description: payload.data.description,
                    category_id: payload.data.category_id,
                    due_date: payload.data.due_date,
                    status: payload.data.status,
                    unit: payload.data.unit,
                    start_date: payload.data.start_date,
                    deadline: payload.data.deadline,
                    judul: payload.data.judul,
                    kategori: payload.data.kategori,
                    favorit: payload.data.favorit,
                    amount: payload.data.amount,
                    note: payload.data.note,
                };
                Object.entries(formValues).forEach(([name, value]) => {
                    if (value === undefined) return;
                    const field = form.elements.namedItem(name);
                    if (!field) return;
                    if (field.type === 'checkbox') field.checked = Boolean(value);
                    else field.value = value ?? '';
                });
            }

            const method = (new FormData(form).get('_method') || form.method || 'POST').toString().toUpperCase();
            if (method === 'DELETE' && form.dataset.ajaxDeletePage === 'true') {
                const main = document.querySelector('main');
                const result = document.createElement('div');
                result.className = 'mx-auto max-w-3xl rounded-xl border border-emerald-200 bg-white p-6 text-sm text-slate-700 shadow-soft';
                const message = document.createElement('p');
                message.className = 'font-semibold';
                message.textContent = payload.message;
                const link = document.createElement('a');
                link.href = payload.redirect_url || '/catatan';
                link.className = 'mt-3 inline-flex font-semibold text-[#2F6F64] underline';
                link.textContent = 'Kembali ke daftar catatan';
                result.append(message, link);
                main?.replaceChildren(result);
                document.getElementById('delete-modal')?.classList.add('hidden');
            } else if (method === 'DELETE') {
                const item = form.closest('[data-ajax-item]');
                item?.remove();
                const list = form.closest('[data-ajax-list]');
                if (list) {
                    list.querySelectorAll('[data-ajax-count]').forEach((counter) => {
                        const nextCount = Math.max(0, Number(counter.dataset.ajaxCount || 0) - 1);
                        counter.dataset.ajaxCount = String(nextCount);
                        counter.textContent = `${nextCount} ${counter.dataset.ajaxCountLabel || ''}`.trim();
                    });
                    if (!list.querySelector('[data-ajax-item]') && !list.querySelector('[data-ajax-empty]')) {
                        const empty = document.createElement('p');
                        empty.dataset.ajaxEmpty = 'true';
                        empty.className = 'rounded-xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center text-sm text-slate-500';
                        empty.textContent = list.dataset.ajaxEmptyMessage || 'Tidak ada data tersisa pada daftar ini.';
                        list.append(empty);
                    }
                }
            } else {
                updateGoalProgress(form, payload.data);
                if (form.dataset.ajaxNoteFavorite && typeof payload.data?.favorit === 'boolean') {
                    const button = form.querySelector('[data-note-favorite-button]');
                    const icon = button?.querySelector('svg');
                    if (button) {
                        button.title = payload.data.favorit ? 'Hapus dari favorit' : 'Tandai sebagai favorit';
                        button.setAttribute('aria-label', button.title);
                        const cardStyles = form.dataset.ajaxNoteFavorite === 'card'
                            ? ['text-amber-500', 'bg-amber-50', 'hover:bg-amber-100', 'text-slate-300', 'hover:text-amber-400', 'hover:bg-slate-50']
                            : ['bg-amber-50', 'border-amber-200', 'text-amber-700', 'hover:bg-amber-100', 'bg-slate-50', 'border-slate-200', 'text-slate-600', 'hover:bg-slate-100'];
                        const activeStyles = form.dataset.ajaxNoteFavorite === 'card'
                            ? ['text-amber-500', 'bg-amber-50', 'hover:bg-amber-100']
                            : ['bg-amber-50', 'border-amber-200', 'text-amber-700', 'hover:bg-amber-100'];
                        const inactiveStyles = form.dataset.ajaxNoteFavorite === 'card'
                            ? ['text-slate-300', 'hover:text-amber-400', 'hover:bg-slate-50']
                            : ['bg-slate-50', 'border-slate-200', 'text-slate-600', 'hover:bg-slate-100'];
                        button.classList.remove(...cardStyles);
                        button.classList.add(...(payload.data.favorit ? activeStyles : inactiveStyles));
                    }
                    icon?.classList.toggle('fill-amber-400', payload.data.favorit);
                    icon?.classList.toggle('text-amber-500', payload.data.favorit);
                    if (form.dataset.ajaxNoteFavorite === 'detail') {
                        icon?.classList.toggle('text-slate-400', !payload.data.favorit);
                    }
                    if (button?.closest('.rounded-xl')) {
                        button.querySelector('span')?.replaceChildren(document.createTextNode(
                            payload.data.favorit ? 'Catatan Favorit' : 'Jadikan Favorit'
                        ));
                    }
                }
                if (form.dataset.ajaxGoalForm === 'true' && payload.data) {
                    const imageInput = form.querySelector('input[type="file"][name="image"]');
                    if (imageInput) imageInput.value = '';
                    const removeImage = form.querySelector('input[name="remove_image"]');
                    if (removeImage) removeImage.checked = false;
                    if (payload.data.image_url) {
                        let image = form.querySelector('[data-goal-form-image]');
                        if (!image) {
                            image = document.createElement('img');
                            image.dataset.goalFormImage = 'true';
                            image.alt = `Gambar motivasi untuk target ${payload.data.name}`;
                            image.className = 'mb-3 max-h-56 w-full rounded-xl object-cover';
                            form.querySelector('input[name="image"]')?.before(image);
                        }
                        image.src = payload.data.image_url;
                    } else {
                        form.querySelector('[data-goal-form-image]')?.remove();
                    }
                }
                form.dispatchEvent(new CustomEvent('catatin:ajax-success', { detail: payload }));
                if (form.dataset.ajaxTaskStatus === 'true' && payload.data?.status) {
                    const card = form.closest('[data-task-item]');
                    const status = card?.querySelector('[data-task-status]');
                    const labels = { pending: 'Pending', in_progress: 'Dikerjakan', completed: 'Tuntas' };
                    if (status && labels[payload.data.status]) {
                        status.textContent = labels[payload.data.status];
                        status.className = `rounded-full px-2.5 py-1 text-xs font-semibold ${
                            payload.data.status === 'completed' ? 'bg-emerald-100 text-emerald-800'
                                : payload.data.status === 'in_progress' ? 'bg-amber-100 text-amber-800'
                                    : 'bg-slate-100 text-slate-700'
                        }`;
                        let dueFlag = card.querySelector('[data-task-due-flag]');
                        if (payload.data.overdue || payload.data.due_soon) {
                            if (!dueFlag) {
                                dueFlag = document.createElement('span');
                                status.insertAdjacentElement('afterend', dueFlag);
                            }
                            dueFlag.dataset.taskDueFlag = payload.data.overdue ? 'overdue' : 'due-soon';
                            dueFlag.className = payload.data.overdue
                                ? 'text-xs font-bold text-rose-700'
                                : 'text-xs font-semibold text-amber-700';
                            dueFlag.textContent = payload.data.overdue ? 'Terlambat' : 'Deadline dekat';
                        } else {
                            dueFlag?.remove();
                        }
                        card.querySelectorAll('form[data-ajax-task-status] input[name="status"]').forEach((input) => {
                            const button = input.closest('form').querySelector('button');
                            if (button) {
                                const isCurrentStatus = input.value === payload.data.status;
                                button.disabled = isCurrentStatus;
                                button.dataset.ajaxDisabledAfterResponse = String(isCurrentStatus);
                            }
                        });
                    }
                }
            }

            showAjaxToast('success', payload.message);
        } catch {
            showAjaxToast(confirmedMessage ? 'success' : 'error', confirmedMessage || 'Koneksi atau server bermasalah. Data belum dapat dikonfirmasi; silakan coba kembali.');
        } finally {
            priorButtons.forEach(({ button, disabled, html, ariaBusy }) => {
                button.disabled = disabled || button.dataset.ajaxDisabledAfterResponse === 'true';
                delete button.dataset.ajaxDisabledAfterResponse;
                button.innerHTML = html;
                if (ariaBusy === null) button.removeAttribute('aria-busy');
                else button.setAttribute('aria-busy', ariaBusy);
            });
            const uploadProgress = form.querySelector('[data-upload-progress]');
            if (uploadProgress) {
                uploadProgress.textContent = '';
                uploadProgress.classList.add('hidden');
            }
            delete form.dataset.ajaxSubmitting;
        }
    });

    const loginModal = document.getElementById('login-required-modal');
    const loginModalLink = document.getElementById('login-required-link');
    const closeLoginModal = () => {
        loginModal?.classList.add('hidden');
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-login-required]');
        if (!trigger || !loginModal || !loginModalLink) return;

        event.preventDefault();
        const loginUrl = new URL(loginModal.dataset.loginUrl, window.location.origin);
        const returnTo = trigger.dataset.returnTo || trigger.getAttribute('href') || window.location.pathname;
        loginUrl.searchParams.set('login_required', '1');
        loginUrl.searchParams.set('redirect_to', returnTo);
        loginModalLink.href = loginUrl.toString();
        loginModal.classList.remove('hidden');
        loginModal.querySelector('[data-login-required-later]')?.focus();
    });

    loginModal?.querySelectorAll('[data-login-required-later], [data-login-required-backdrop]').forEach((element) => {
        element.addEventListener('click', closeLoginModal);
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-delete-attachment]');
        if (!button || !window.confirm('Hapus lampiran ini?')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = button.dataset.action;
        for (const [name, value] of [['_token', button.dataset.token], ['_method', 'DELETE']]) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.append(input);
        }
        document.body.append(form);
        form.submit();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && loginModal && !loginModal.classList.contains('hidden')) {
            closeLoginModal();
        }
    });

    document.querySelectorAll('[data-document-dropzone]').forEach((dropzone) => {
        const input = dropzone.querySelector('[data-document-input]');
        const browse = dropzone.querySelector('[data-document-browse]');
        const list = dropzone.querySelector('[data-document-list]');
        const countLabel = dropzone.querySelector('[data-document-count]');
        const sizeLabel = dropzone.querySelector('[data-document-size]');
        const errorLabel = dropzone.querySelector('[data-document-error]');
        const form = input?.closest('form');
        const selectedFiles = [];
        const currentCount = Number(dropzone.dataset.currentCount || 0);
        const currentBytes = Number(dropzone.dataset.currentBytes || 0);
        const maxFileBytes = Number(dropzone.dataset.maxFileBytes || 52428800);
        const maxFiles = Number(dropzone.dataset.maxFiles || 10);
        const maxBytes = Number(dropzone.dataset.maxBytes || 104857600);
        const formatBytes = (bytes) => `${(bytes / 1048576).toFixed(1)} MB`;

        const updateFiles = () => {
            list.replaceChildren();
            const totalBytes = currentBytes + selectedFiles.reduce((sum, file) => sum + file.size, 0);
            const totalCount = currentCount + selectedFiles.length;
            const oversizedFile = selectedFiles.some((file) => file.size > maxFileBytes);
            const isOverLimit = totalCount > maxFiles || totalBytes > maxBytes || oversizedFile;

            selectedFiles.forEach((file, index) => {
                const row = document.createElement('li');
                row.className = 'flex items-center justify-between gap-3 p-2.5 bg-white border border-slate-200 rounded-md';
                const details = document.createElement('span');
                details.className = 'min-w-0 text-xs text-slate-700 truncate';
                details.textContent = `${file.name} · ${(file.name.split('.').pop() || '').toUpperCase()} · ${formatBytes(file.size)}`;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'shrink-0 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 rounded';
                remove.textContent = 'Hapus pilihan';
                remove.setAttribute('aria-label', `Hapus pilihan ${file.name}`);
                remove.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    updateFiles();
                });
                row.append(details, remove);
                list.append(row);
            });

            const transfer = new DataTransfer();
            selectedFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            countLabel.textContent = `${totalCount} / ${maxFiles} file`;
            sizeLabel.textContent = `${formatBytes(totalBytes)} / 100 MB digunakan · ${formatBytes(Math.max(0, maxBytes - totalBytes))} tersisa`;
            errorLabel.textContent = isOverLimit
                ? (oversizedFile ? 'Ukuran setiap file tidak boleh melebihi 50 MB.' : totalCount > maxFiles ? 'Jumlah lampiran melebihi batas 10 file.' : 'Total ukuran lampiran melebihi 100 MB.')
                : '';
            errorLabel.classList.toggle('hidden', !isOverLimit);
        };

        browse.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            const additions = Array.from(input.files || []);
            input.value = '';
            selectedFiles.push(...additions);
            updateFiles();
        });

        ['dragenter', 'dragover'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('border-[#2F6F64]', 'bg-emerald-50');
        }));
        ['dragleave', 'drop'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('border-[#2F6F64]', 'bg-emerald-50');
        }));
        dropzone.addEventListener('drop', (event) => {
            selectedFiles.push(...Array.from(event.dataTransfer.files || []));
            updateFiles();
        });
        form?.addEventListener('submit', () => {
            const progress = form.querySelector('[data-upload-progress]');
            if (selectedFiles.length && progress) {
                progress.textContent = 'Sedang mengunggah lampiran…';
                progress.classList.remove('hidden');
            }
        });
        form?.addEventListener('catatin:ajax-success', () => {
            selectedFiles.splice(0, selectedFiles.length);
            updateFiles();
        });
    });

    const colorInput = document.getElementById('custom-theme-color');
    const navigationFooterInput = document.getElementById('custom-navigation-footer-color');
    const motionToggle = document.getElementById('reduce-motion');
    const status = document.getElementById('theme-status');
    const swatches = document.querySelectorAll('[data-theme-color]');
    const navigationFooterSwatches = document.querySelectorAll('[data-navigation-footer-color]');
    const themeForm = document.getElementById('theme-settings-form');
    const accentValue = colorInput;

    if (!colorInput || !navigationFooterInput || !motionToggle) return;

    let settings = readSettings();

    const updateControls = () => {
        colorInput.value = settings.accent;
        navigationFooterInput.value = settings.navigationFooter;
        if (accentValue) accentValue.value = settings.accent;
        motionToggle.checked = Boolean(settings.reduceMotion);
        swatches.forEach((swatch) => {
            swatch.setAttribute('aria-pressed', String(swatch.dataset.themeColor.toLowerCase() === settings.accent.toLowerCase()));
        });
        navigationFooterSwatches.forEach((swatch) => {
            swatch.setAttribute('aria-pressed', String(swatch.dataset.navigationFooterColor.toLowerCase() === settings.navigationFooter.toLowerCase()));
        });
    };

    const updateTheme = (accent, navigationFooter = settings.navigationFooter) => {
        settings = { ...settings, accent, navigationFooter };
        saveSettings(settings);
        updateControls();
        if (status) status.textContent = 'Pratinjau diterapkan. Simpan perubahan agar palet tersimpan pada akun Anda.';
    };

    swatches.forEach((swatch) => {
        swatch.addEventListener('click', () => updateTheme(swatch.dataset.themeColor));
    });

    colorInput.addEventListener('input', (event) => updateTheme(event.target.value));
    navigationFooterSwatches.forEach((swatch) => {
        swatch.addEventListener('click', () => updateTheme(settings.accent, swatch.dataset.navigationFooterColor));
    });
    navigationFooterInput.addEventListener('input', (event) => updateTheme(settings.accent, event.target.value));
    motionToggle.addEventListener('change', () => {
        settings = { ...settings, reduceMotion: motionToggle.checked };
        saveSettings(settings);
        if (status) status.textContent = 'Preferensi aksesibilitas diperbarui.';
    });

    document.getElementById('reset-theme')?.addEventListener('click', () => {
        settings = { ...defaultSettings, reduceMotion: settings.reduceMotion };
        saveSettings(settings);
        updateControls();
        if (themeForm?.dataset.authenticated === 'true') {
            themeForm.requestSubmit();
        } else if (status) {
            status.textContent = 'Pratinjau dikembalikan. Masuk untuk menyimpan perubahan pada akun Anda.';
        }
    });

    updateControls();
});

// Helper for re-initializing Lucide icons dynamically if needed
window.refreshLucideIcons = () => {
    createIcons({ icons });
};
