/* Agenda-planner — vanilla interactions for the weekplanning module. Server-
   rendered Blade; this layer switches the Weekplan/Voornemens tabs, generates a
   weekplan (POST generate → reload), accepts a proposed block (POST
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

    /* ---------------- intentions: toggle active / delete / add ---------------- */
    const intentionTpl = root.dataset.intentionTpl;
    const intentionsUrl = root.dataset.intentionsUrl;

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
            if (!window.confirm('Dit voornemen verwijderen?')) return;
            await del(fillTpl(intentionTpl, id)).catch(() => {});
            card.remove();
        });
    });

    root.querySelector('[data-ag-int-add]')?.addEventListener('click', async () => {
        const title = (window.prompt('Welk voornemen wil je toevoegen?') ?? '').trim();
        if (!title) return;
        if (!intentionsUrl) return;
        await post(intentionsUrl, {
            title,
            category: 'custom',
            frequency_type: 'weekly',
            target_min: 1,
            target_max: 1,
            duration_minutes: 60,
        }).catch(() => {});
        window.location.reload();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlanner, { once: true });
} else {
    initPlanner();
}
document.addEventListener('livewire:navigated', initPlanner);
