/* Gewoontes & Onderhoud — interactive layer: tab switching, optimistic habit
   completion / undo, maintenance "afvinken" (reschedule) and the create modal. */

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

const TAB_TITLES = { gewoontes: 'Gewoontes', onderhoud: 'Onderhoud' };
const CREATE_LABELS = { gewoontes: 'Nieuwe gewoonte', onderhoud: 'Nieuwe onderhoudstaak' };

const initHabits = () => {
    const root = document.querySelector('[data-habits]');
    if (!root || root.dataset.habitsReady === 'true') {
        return;
    }
    root.dataset.habitsReady = 'true';

    let currentTab = 'gewoontes';

    /* -------------------- tabs -------------------- */
    const setTab = (name) => {
        currentTab = name;
        root.querySelectorAll('[data-hb-tab]').forEach((btn) =>
            btn.classList.toggle('on', btn.dataset.hbTab === name),
        );
        root.querySelectorAll('[data-hb-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.hbPanel !== name;
        });
        const title = root.querySelector('[data-hb-page-title]');
        if (title) title.textContent = TAB_TITLES[name];
        const label = root.querySelector('[data-hb-create-label]');
        if (label) label.textContent = CREATE_LABELS[name];
        root.querySelectorAll('[data-hb-sub]').forEach((sub) => {
            sub.hidden = sub.dataset.hbSub !== name;
        });
    };

    root.querySelectorAll('[data-hb-tab]').forEach((btn) => {
        btn.addEventListener('click', () => setTab(btn.dataset.hbTab));
    });

    /* -------------------- habit completion -------------------- */
    const applyHabitState = (card, nowDone) => {
        const type = card.dataset.hbType;
        const target = parseInt(card.dataset.hbTarget, 10) || 0;

        card.classList.toggle('done', nowDone);
        card.classList.remove('rest');

        const check = card.querySelector('[data-hb-toggle]');
        if (check) {
            check.setAttribute('aria-pressed', nowDone ? 'true' : 'false');
            check.title = nowDone ? 'Ongedaan maken' : 'Vandaag afvinken';
        }

        const tagDone = card.querySelector('[data-hb-tag-done]');
        if (tagDone) tagDone.hidden = !nowDone;
        const undo = card.querySelector('[data-hb-undo]');
        if (undo) undo.hidden = !nowDone;

        const progDone = card.querySelector('[data-hb-prog-done]');

        if (type === 'count') {
            let done = parseInt(card.dataset.hbDone, 10) || 0;
            done = Math.max(0, Math.min(target, done + (nowDone ? 1 : -1)));
            card.dataset.hbDone = String(done);
            if (progDone) progDone.textContent = String(done);

            const reached = done >= target && target > 0;
            const segs = card.querySelectorAll('[data-hb-seg] i');
            segs.forEach((seg, i) => {
                seg.classList.toggle('fill', i < done && !reached);
                seg.classList.toggle('full', i < done && reached);
            });
            const reachedTag = card.querySelector('[data-hb-prog-reached]');
            if (reachedTag) reachedTag.hidden = !reached;
        } else {
            const todayCell = card.querySelector('.hb-day.today');
            if (todayCell) {
                todayCell.classList.toggle('done', nowDone);
                todayCell.classList.toggle('sched', !nowDone);
                const dot = todayCell.querySelector('.hb-day-c');
                if (dot) dot.innerHTML = nowDone
                    ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12l5 5L20 6"/></svg>'
                    : '';
            }
            const weekDone = card.querySelectorAll('.hb-day.done').length;
            if (progDone) progDone.textContent = String(weekDone);
        }

        const streakEl = card.querySelector('[data-hb-streak]');
        const streakN = card.querySelector('[data-hb-streak-n]');
        if (streakN) {
            let streak = parseInt(streakN.textContent, 10) || 0;
            streak = Math.max(0, streak + (nowDone ? 1 : -1));
            streakN.textContent = String(streak);
            if (streakEl) streakEl.classList.toggle('zero', streak === 0);
        }
    };

    const toggleHabit = (card) => {
        const check = card.querySelector('[data-hb-toggle]');
        if (!check || check.classList.contains('disabled')) return;

        const nowDone = !card.classList.contains('done');
        const url = check.dataset.hbCompleteUrl;
        applyHabitState(card, nowDone);

        send(url, nowDone ? 'POST' : 'DELETE', { date: root.dataset.date })
            .then((res) => {
                if (!res.ok) throw new Error('failed');
            })
            .catch(() => window.location.reload());
    };

    root.querySelectorAll('[data-hb-card]').forEach((card) => {
        card.querySelector('[data-hb-toggle]')?.addEventListener('click', () => toggleHabit(card));
        card.querySelector('[data-hb-undo]')?.addEventListener('click', () => toggleHabit(card));
    });

    /* -------------------- maintenance afvinken -------------------- */
    root.querySelectorAll('[data-hb-maction]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('[data-hb-mrow]');
            if (!row || row.classList.contains('done')) return;

            row.classList.add('done');
            row.classList.remove('overdue', 'soon');
            btn.classList.add('is-done');
            const label = btn.querySelector('[data-hb-maction-label]');
            if (label) label.textContent = 'Gedaan';
            const rel = row.querySelector('[data-hb-due-rel]');
            if (rel) rel.textContent = 'Opnieuw gepland';
            const abs = row.querySelector('[data-hb-due-abs]');
            if (abs) abs.textContent = '+ 1 periode';
            row.querySelector('[data-hb-onboard]')?.remove();

            send(btn.dataset.hbCompleteUrl, 'POST', {})
                .then((res) => {
                    if (!res.ok) throw new Error('failed');
                })
                .catch(() => window.location.reload());
        });
    });

    /* -------------------- create modal -------------------- */
    const modal = root.querySelector('[data-hb-modal]');
    const form = root.querySelector('[data-hb-form]');
    const errorBox = root.querySelector('[data-hb-error]');
    const errorTx = root.querySelector('[data-hb-error-tx]');

    const showError = (message) => {
        if (errorBox && errorTx) {
            errorTx.textContent = message;
            errorBox.hidden = false;
        }
    };

    const syncHabitCadenceFields = () => {
        const cadence = root.querySelector('[data-hb-cadence]')?.value;
        root.querySelectorAll('[data-hb-cfield]').forEach((field) => {
            field.hidden = field.dataset.hbCfield !== cadence;
        });
    };

    const openModal = (type, preset = null) => {
        if (!modal || !form) return;
        if (errorBox) errorBox.hidden = true;

        const isMaint = type === 'maintenance';
        form.querySelector('[data-hb-form-type]').value = isMaint ? 'maintenance' : 'habit';
        root.querySelector('[data-hb-modal-title]').textContent = CREATE_LABELS[isMaint ? 'onderhoud' : 'gewoontes'];
        root.querySelector('[data-hb-mfield="habit"]').hidden = isMaint;
        root.querySelector('[data-hb-mfield="maintenance"]').hidden = !isMaint;

        if (preset) {
            form.querySelector('[data-hb-form-title]').value = preset.title ?? '';
            const cadenceSel = root.querySelector('[data-hb-cadence]');
            if (cadenceSel && preset.cadence_type) cadenceSel.value = preset.cadence_type;
            if (preset.times) root.querySelector('[data-hb-times]').value = preset.times;
            root.querySelectorAll('[data-hb-wd]').forEach((wd) => {
                wd.classList.toggle('on', Array.isArray(preset.weekdays) && preset.weekdays.includes(Number(wd.dataset.hbWd)));
            });
            syncHabitCadenceFields();
        }

        modal.hidden = false;
        form.querySelector('[data-hb-form-title]')?.focus();
    };

    const closeModal = () => {
        if (modal) modal.hidden = true;
    };

    root.querySelectorAll('[data-hb-create]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.hbCreateType
                || (currentTab === 'onderhoud' ? 'maintenance' : 'habit');
            openModal(type);
        });
    });

    root.querySelectorAll('[data-hb-suggest]').forEach((chip) => {
        chip.addEventListener('click', () => {
            try {
                openModal('habit', JSON.parse(chip.dataset.hbSuggest));
            } catch {
                openModal('habit');
            }
        });
    });

    root.querySelectorAll('[data-hb-modal-close]').forEach((btn) => btn.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });

    root.querySelector('[data-hb-cadence]')?.addEventListener('change', syncHabitCadenceFields);
    root.querySelectorAll('[data-hb-wd]').forEach((wd) => {
        wd.addEventListener('click', () => wd.classList.toggle('on'));
    });

    const buildPayload = () => {
        const type = form.querySelector('[data-hb-form-type]').value;
        const title = form.querySelector('[data-hb-form-title]').value.trim();
        if (!title) return { error: 'Geef een titel op.' };

        if (type === 'maintenance') {
            const interval = Math.max(1, parseInt(root.querySelector('[data-hb-interval]').value, 10) || 1);
            const unit = root.querySelector('[data-hb-unit]').value;
            const due = root.querySelector('[data-hb-due]').value || root.dataset.date;
            return {
                payload: {
                    type: 'maintenance',
                    title,
                    cadence_type: 'interval',
                    cadence_config: { interval, unit },
                    next_due_on: due,
                },
            };
        }

        const cadence = root.querySelector('[data-hb-cadence]').value;
        if (cadence === 'times_per_week') {
            const times = Math.max(1, Math.min(7, parseInt(root.querySelector('[data-hb-times]').value, 10) || 1));
            return { payload: { type: 'habit', title, cadence_type: 'times_per_week', cadence_config: { times } } };
        }
        if (cadence === 'daily') {
            return { payload: { type: 'habit', title, cadence_type: 'weekdays', cadence_config: { weekdays: [1, 2, 3, 4, 5, 6, 7] } } };
        }
        const weekdays = Array.from(root.querySelectorAll('[data-hb-wd].on')).map((wd) => Number(wd.dataset.hbWd));
        if (weekdays.length === 0) return { error: 'Kies minstens één weekdag.' };
        return { payload: { type: 'habit', title, cadence_type: 'weekdays', cadence_config: { weekdays } } };
    };

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (errorBox) errorBox.hidden = true;

        const { payload, error } = buildPayload();
        if (error) {
            showError(error);
            return;
        }

        const submit = form.querySelector('[data-hb-submit]');
        if (submit) submit.disabled = true;

        send(root.dataset.storeUrl, 'POST', payload)
            .then((res) => {
                if (!res.ok) throw new Error('failed');
                window.location.reload();
            })
            .catch(() => {
                if (submit) submit.disabled = false;
                showError('Aanmaken mislukt. Probeer het opnieuw.');
            });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHabits, { once: true });
} else {
    initHabits();
}
document.addEventListener('livewire:navigated', initHabits);
