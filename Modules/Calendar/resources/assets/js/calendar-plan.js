/* Calendar planner — vanilla interactions for the week-planning tabs. Server-
   rendered Blade; this layer switches the Week plan/Habits tabs, generates a
   week plan (POST generate → reload), accepts a proposed block (POST
   items/{id}/accept), rejects one (POST items/{id}/reject), accepts all (POST
   accept-all → reload), and creates/toggles/deletes intentions
   (POST/PATCH/DELETE intentions). The Google connect button is a plain link. */

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
        body: body === null ? undefined : JSON.stringify(body),
    });

const post = (url, body = {}) => send(url, 'POST', body);
const patch = (url, body = {}) => send(url, 'PATCH', body);
const del = (url) => send(url, 'DELETE');

const fillTpl = (tpl, id) => String(tpl || '').replace('__ID__', encodeURIComponent(id));

const initPlanner = () => {
    const root = document.querySelector('[data-ag]');
    if (!root || root.dataset.agReady === 'true') {
        return;
    }
    root.dataset.agReady = 'true';

    /* ---------------- tabs ---------------- */
    const tabs = root.querySelectorAll('[data-ag-tab]');
    const panels = root.querySelectorAll('[data-ag-panel]');
    const subs = root.querySelectorAll('[data-ag-sub]');

    const setTab = (tab) => {
        tabs.forEach((t) => t.classList.toggle('on', t.dataset.agTab === tab));
        panels.forEach((p) => (p.hidden = p.dataset.agPanel !== tab));
        subs.forEach((s) => (s.hidden = s.dataset.agSub !== tab));
    };
    tabs.forEach((t) => t.addEventListener('click', () => setTab(t.dataset.agTab)));

    /* ---------------- generate (regenerate / first generate) ---------------- */
    const generateUrl = root.dataset.generateUrl;
    root.querySelectorAll('[data-ag-generate]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            root.querySelectorAll('[data-ag-generate]').forEach((b) => (b.disabled = true));
            btn.querySelector('.ic')?.classList.add('spin');
            if (generateUrl) {
                await post(generateUrl).catch(() => {});
            }
            window.location.reload();
        });
    });

    /* ---------------- accept all ---------------- */
    const acceptAllUrl = root.dataset.acceptAllUrl;
    root.querySelectorAll('[data-ag-accept-all]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            if (acceptAllUrl) {
                await post(acceptAllUrl).catch(() => {});
            }
            window.location.reload();
        });
    });

    /* ---------------- accept / reject a single proposed block ---------------- */
    const acceptTpl = root.dataset.acceptTpl;
    const rejectTpl = root.dataset.rejectTpl;
    root.querySelectorAll('[data-ag-prop]').forEach((row) => {
        const id = row.dataset.agProp;
        if (!id) return;

        row.querySelector('[data-ag-accept]')?.addEventListener('click', async () => {
            row.querySelectorAll('button').forEach((b) => (b.disabled = true));
            await post(fillTpl(acceptTpl, id)).catch(() => {});
            window.location.reload();
        });

        row.querySelector('[data-ag-reject]')?.addEventListener('click', async () => {
            row.querySelectorAll('button').forEach((b) => (b.disabled = true));
            await post(fillTpl(rejectTpl, id)).catch(() => {});
            window.location.reload();
        });
    });

    /* ---------------- goals: toggle active / delete / add ---------------- */
    const intentionTpl = root.dataset.goalTpl;
    const intentionsUrl = root.dataset.goalsUrl;

    root.querySelectorAll('[data-ag-int]').forEach((card) => {
        const id = card.dataset.agInt;
        if (!id) return;

        card.querySelector('[data-ag-int-toggle]')?.addEventListener('click', async () => {
            const next = card.dataset.agIntActive !== 'true';
            card.dataset.agIntActive = next ? 'true' : 'false';
            const toggle = card.querySelector('[data-ag-int-toggle]');
            toggle?.classList.toggle('on', next);
            toggle?.setAttribute('aria-checked', next ? 'true' : 'false');
            card.classList.toggle('off', !next);
            await patch(fillTpl(intentionTpl, id), { active: next }).catch(() => {});
        });

        card.querySelector('[data-ag-int-delete]')?.addEventListener('click', async () => {
            if (!window.confirm('Delete this habit?')) return;
            await del(fillTpl(intentionTpl, id)).catch(() => {});
            card.remove();
        });
    });

    /* ---------------- habit completion (streak check-off) ---------------- */
    const hbDate = root.querySelector('[data-hb-root]')?.dataset.date || '';

    const applyHabitState = (card, nowDone) => {
        const type = card.dataset.hbType;
        const target = parseInt(card.dataset.hbTarget, 10) || 0;

        card.classList.toggle('done', nowDone);
        card.classList.remove('rest');

        const check = card.querySelector('[data-hb-toggle]');
        if (check) check.setAttribute('aria-pressed', nowDone ? 'true' : 'false');
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
            card.querySelectorAll('[data-hb-seg] i').forEach((seg, i) => {
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
            if (progDone) progDone.textContent = String(card.querySelectorAll('.hb-day.done').length);
        }

        const streakEl = card.querySelector('[data-hb-streak]');
        const streakN = card.querySelector('[data-hb-streak-n]');
        if (streakN) {
            const streak = Math.max(0, (parseInt(streakN.textContent, 10) || 0) + (nowDone ? 1 : -1));
            streakN.textContent = String(streak);
            if (streakEl) streakEl.classList.toggle('zero', streak === 0);
        }
    };

    const toggleHabit = (card) => {
        const check = card.querySelector('[data-hb-toggle]');
        if (!check || check.classList.contains('disabled')) return;
        const nowDone = !card.classList.contains('done');
        applyHabitState(card, nowDone);
        send(check.dataset.hbCompleteUrl, nowDone ? 'POST' : 'DELETE', { date: hbDate })
            .then((res) => { if (!res.ok) throw new Error('failed'); })
            .catch(() => window.location.reload());
    };

    root.querySelectorAll('[data-hb-card]').forEach((card) => {
        card.querySelector('[data-hb-toggle]')?.addEventListener('click', () => toggleHabit(card));
        card.querySelector('[data-hb-undo]')?.addEventListener('click', () => toggleHabit(card));
    });

    /* ---------------- create habit modal ---------------- */
    const modal = root.querySelector('[data-hb-modal]');
    const form = root.querySelector('[data-hb-form]');
    const errorBox = root.querySelector('[data-hb-error]');
    const errorTx = root.querySelector('[data-hb-error-tx]');

    const syncFreq = () => {
        const isTimes = root.querySelector('[data-hb-freq]')?.value === 'times_per_week';
        const timesField = root.querySelector('[data-hb-times-field]');
        if (timesField) timesField.hidden = !isTimes;
    };
    root.querySelector('[data-hb-freq]')?.addEventListener('change', syncFreq);

    const openModal = () => {
        if (!modal) return;
        if (errorBox) errorBox.hidden = true;
        syncFreq();
        modal.hidden = false;
        form?.querySelector('[data-hb-form-title]')?.focus();
    };
    const closeModal = () => { if (modal) modal.hidden = true; };

    root.querySelectorAll('[data-hb-create]').forEach((btn) => btn.addEventListener('click', openModal));
    root.querySelectorAll('[data-hb-modal-close]').forEach((btn) => btn.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal && !modal.hidden) closeModal(); });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (errorBox) errorBox.hidden = true;
        const title = (form.querySelector('[data-hb-form-title]')?.value ?? '').trim();
        if (!title) {
            if (errorBox && errorTx) { errorTx.textContent = 'Enter a title.'; errorBox.hidden = false; }
            return;
        }
        const freq = root.querySelector('[data-hb-freq]')?.value ?? 'times_per_week';
        const times = Math.max(1, Math.min(7, parseInt(root.querySelector('[data-hb-times]')?.value, 10) || 1));
        const target = freq === 'weekly' ? 1 : times;
        const payload = {
            title,
            category: root.querySelector('[data-hb-category]')?.value ?? 'custom',
            frequency_type: freq,
            target_min: target,
            target_max: target,
            duration_minutes: Math.max(15, Math.min(480, parseInt(root.querySelector('[data-hb-duration]')?.value, 10) || 60)),
            plannable: !!root.querySelector('[data-hb-plannable]')?.checked,
        };
        const submit = form.querySelector('[data-hb-submit]');
        if (submit) submit.disabled = true;
        if (!intentionsUrl) return;
        await post(intentionsUrl, payload).catch(() => {});
        window.location.reload();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlanner, { once: true });
} else {
    initPlanner();
}
document.addEventListener('livewire:navigated', initPlanner);
