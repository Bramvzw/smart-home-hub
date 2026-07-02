/* Maintenance page — mark recurring maintenance done (reschedules) and the
   create modal. */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const send = (url, method, body = null) =>
    fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : null,
    });

const initMaintenance = () => {
    const root = document.querySelector('[data-maintenance]');
    if (!root || root.dataset.maintenanceReady === 'true') {
        return;
    }
    root.dataset.maintenanceReady = 'true';

    /* -------------------- mark done -------------------- */
    root.querySelectorAll('[data-hb-maction]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('[data-hb-mrow]');
            if (!row || row.classList.contains('done')) return;

            row.classList.add('done');
            row.classList.remove('overdue', 'soon');
            btn.classList.add('is-done');
            const label = btn.querySelector('[data-hb-maction-label]');
            if (label) label.textContent = 'Done';
            const rel = row.querySelector('[data-hb-due-rel]');
            if (rel) rel.textContent = 'Rescheduled';
            const abs = row.querySelector('[data-hb-due-abs]');
            if (abs) abs.textContent = '+ 1 period';
            row.querySelector('[data-hb-onboard]')?.remove();

            send(btn.dataset.hbCompleteUrl, 'POST', {})
                .then((res) => { if (!res.ok) throw new Error('failed'); })
                .catch(() => window.location.reload());
        });
    });

    /* -------------------- create modal -------------------- */
    const modal = root.querySelector('[data-hb-modal]');
    const form = root.querySelector('[data-hb-form]');
    const errorBox = root.querySelector('[data-hb-error]');
    const errorTx = root.querySelector('[data-hb-error-tx]');

    const openModal = () => {
        if (!modal) return;
        if (errorBox) errorBox.hidden = true;
        modal.hidden = false;
        form?.querySelector('[data-hb-form-title]')?.focus();
    };
    const closeModal = () => { if (modal) modal.hidden = true; };

    root.querySelectorAll('[data-hb-create]').forEach((btn) => btn.addEventListener('click', openModal));
    root.querySelectorAll('[data-hb-modal-close]').forEach((btn) => btn.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal && !modal.hidden) closeModal(); });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (errorBox) errorBox.hidden = true;

        const title = (form.querySelector('[data-hb-form-title]')?.value ?? '').trim();
        if (!title) {
            if (errorBox && errorTx) { errorTx.textContent = 'Enter a title.'; errorBox.hidden = false; }
            return;
        }

        const interval = Math.max(1, parseInt(root.querySelector('[data-hb-interval]').value, 10) || 1);
        const unit = root.querySelector('[data-hb-unit]').value;
        const due = root.querySelector('[data-hb-due]').value || root.dataset.date;

        const submit = form.querySelector('[data-hb-submit]');
        if (submit) submit.disabled = true;

        send(root.dataset.storeUrl, 'POST', {
            type: 'maintenance',
            title,
            cadence_type: 'interval',
            cadence_config: { interval, unit },
            next_due_on: due,
        })
            .then((res) => {
                if (!res.ok) throw new Error('failed');
                window.location.reload();
            })
            .catch(() => {
                if (submit) submit.disabled = false;
                if (errorBox && errorTx) { errorTx.textContent = 'Creating failed. Please try again.'; errorBox.hidden = false; }
            });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMaintenance, { once: true });
} else {
    initMaintenance();
}
document.addEventListener('livewire:navigated', initMaintenance);
