/* Recepten — vanilla interactions for the weekmenu module. Server-rendered
   Blade; this layer switches tabs, renders a recipe detail panel from the
   embedded recipe payload, handles the shopping-list checkboxes, and wires
   "opnieuw genereren" to the generate endpoint. */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const post = (url, body = {}) =>
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });

const euro = (n) =>
    '€ ' + Number(n).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const STORE_SHORT = { ah: 'AH', lidl: 'Lidl' };
const storeShort = (key) => STORE_SHORT[String(key).toLowerCase()] ?? String(key).toUpperCase();

/* line-icon factory mirroring the Blade $RIc closure (only the icons the
   detail view needs). */
const RIC = {
    ArrowL: '<path d="M19 12H5M11 18l-6-6 6-6"/>',
    Clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    Users: '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M17.5 19a5.5 5.5 0 0 0-2.5-4.6"/>',
    Euro: '<path d="M16.5 6.5A6 6 0 1 0 16.5 17.5"/><path d="M4 10.5h8M4 13.5h7"/>',
    List: '<path d="M8 6.5h12M8 12h12M8 17.5h12"/><circle cx="4" cy="6.5" r="1.1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.1" fill="currentColor" stroke="none"/><circle cx="4" cy="17.5" r="1.1" fill="currentColor" stroke="none"/>',
    Flame: '<path d="M12 3s5 4.5 5 9a5 5 0 0 1-10 0c0-1.8.8-3 .8-3 .5 1.2 1.7 1.6 1.7 1.6C9 8 12 3 12 3z"/>',
    Cart: '<circle cx="9.5" cy="20" r="1.4" fill="currentColor" stroke="none"/><circle cx="17.5" cy="20" r="1.4" fill="currentColor" stroke="none"/><path d="M2.5 4h2.2l2.1 11.2a1.5 1.5 0 0 0 1.5 1.3h8.4a1.5 1.5 0 0 0 1.5-1.2L20.5 8H6"/>',
    CheckSm: '<path d="M4 12l5 5L20 6"/>',
    Bowl: '<path d="M3 10.5h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8z"/><path d="M9 6.5c0-1.5 1.2-2 1.2-3M13 6.5c0-1.5 1.2-2 1.2-3"/>',
    Wok: '<path d="M3 11h18a9 9 0 0 1-9 8 9 9 0 0 1-9-8z"/><path d="M21 11l1.5-1.5M3 11 1.5 9.5"/>',
    Fish: '<path d="M3 12c4-5 11-5 15 0-4 5-11 5-15 0z"/><path d="M18 12c1.5-1.5 3-1.5 3-1.5s0 3-3 3M8.5 11h.01"/>',
    Pot: '<path d="M4 9h16v5a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6z"/><path d="M2.5 9h19M7 9 6 5.5M17 9l1-3.5"/>',
    Leaf: '<path d="M4 20C4 11 11 4 20 4c0 9-7 16-16 16z"/><path d="M4 20c4.5-5 8-7.5 12-9"/>',
};

const icon = (name, size = 16, stroke = 1.7, cls = '') => {
    const inner = RIC[name] || RIC.Bowl;
    return `<svg class="${cls}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${stroke}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
};

const esc = (s) =>
    String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

const storeTag = (store) => {
    if (!store) return '';
    const key = String(store).toLowerCase();
    return `<span class="rc-ingr-store ${esc(key)}">${esc(storeShort(key))}</span>`;
};

/* steps may contain a small amount of inline markup (<b>) from the source;
   allow only <b>/</b> and escape the rest. */
const stepHtml = (step) =>
    esc(step)
        .replace(/&lt;b&gt;/g, '<b>')
        .replace(/&lt;\/b&gt;/g, '</b>');

const renderDetail = (recipe) => {
    const ingredients = recipe.ingredients || [];
    const steps = recipe.steps || [];
    // shopping list = explicit list when present, otherwise the ingredients;
    // on-offer items first, mirroring the design.
    const baseList = (recipe.shopping_list && recipe.shopping_list.length ? recipe.shopping_list : ingredients).map(
        (it, i) => ({ ...it, key: recipe.id + '-' + i })
    );
    const shopItems = [...baseList.filter((x) => x.on_offer), ...baseList.filter((x) => !x.on_offer)];

    const costPill =
        recipe.estimated_cost !== null && recipe.estimated_cost !== undefined
            ? `<span class="rc-meta-pill acc tnum">${icon('Euro', 14, 1.7, 'ic')} ${euro(recipe.estimated_cost)}</span>`
            : '';

    const ingredientRows = ingredients
        .map((ing) => {
            const offer = !!ing.on_offer;
            return `<div class="rc-ingr ${offer ? 'offer' : ''}">
                <span class="rc-ingr-mark"></span>
                <span class="rc-ingr-name">${esc(ing.name)}</span>
                ${offer ? storeTag(ing.store) : ''}
                ${ing.amount ? `<span class="rc-ingr-qty tnum">${esc(ing.amount)}</span>` : ''}
            </div>`;
        })
        .join('');

    const stepRows = steps
        .map(
            (s, i) => `<div class="rc-step">
                <span class="rc-step-num tnum">${i + 1}</span>
                <span class="rc-step-tx">${stepHtml(s)}</span>
            </div>`
        )
        .join('');

    const shopRows = shopItems
        .map(
            (it) => `<button class="rc-shop-row" type="button" data-rc-shop="${esc(it.key)}">
                <span class="rc-check">${icon('CheckSm', 14, 2.4)}</span>
                <span class="rc-shop-name">${esc(it.name)}</span>
                ${it.on_offer ? storeTag(it.store) : ''}
                ${it.amount ? `<span class="rc-shop-qty tnum">${esc(it.amount)}</span>` : ''}
            </button>`
        )
        .join('');

    return `<button class="rc-back" type="button" data-rc-back>${icon('ArrowL', 15)} Terug naar weekmenu</button>

    <div class="rc-detail-head">
        <div class="rc-detail-thumb">${icon(recipe.icon || 'Bowl', 48, 1.5, 'ic')}</div>
        <div class="rc-detail-head-b">
            <h1 class="rc-detail-title disp">${esc(recipe.title)}</h1>
            ${recipe.description ? `<div class="rc-detail-desc">${esc(recipe.description)}</div>` : ''}
            <div class="rc-detail-meta">
                <span class="rc-meta-pill">${icon('Clock', 14, 1.7, 'ic')} ${Number(recipe.time_minutes) || 0} min</span>
                <span class="rc-meta-pill">${icon('Users', 14, 1.7, 'ic')} ${Number(recipe.servings) || 0} personen</span>
                ${costPill}
            </div>
        </div>
    </div>

    <div class="rc-detail-grid">
        <div>
            <div class="rc-panel">
                <div class="rc-panel-head">
                    ${icon('List', 17, 1.7, 'ic')}
                    <span class="rc-panel-title">Ingrediënten</span>
                    <span class="rc-panel-count tnum">${ingredients.length}</span>
                </div>
                <div class="rc-ingr-list">${ingredientRows}</div>
            </div>

            <div class="rc-panel">
                <div class="rc-panel-head">
                    ${icon('Flame', 17, 1.7, 'ic')}
                    <span class="rc-panel-title">Bereiding</span>
                    <span class="rc-panel-count tnum">${steps.length} stappen</span>
                </div>
                <div class="rc-steps">${stepRows}</div>
            </div>
        </div>

        <div>
            <div class="rc-panel">
                <div class="rc-panel-head">
                    ${icon('Cart', 17, 1.7, 'ic')}
                    <span class="rc-panel-title">Boodschappenlijst</span>
                    <span class="rc-panel-count tnum" data-rc-shop-count>0/${shopItems.length}</span>
                </div>
                <div class="rc-shop" data-rc-shop-list>${shopRows}</div>
                <div class="rc-shop-foot">
                    <span class="rc-shop-prog"><b class="tnum" data-rc-shop-done>0</b> van <b class="tnum">${shopItems.length}</b> afgevinkt</span>
                    <button class="rc-shop-clear" type="button" data-rc-shop-clear disabled>Lijst wissen</button>
                </div>
            </div>
        </div>
    </div>`;
};

const initRecipes = () => {
    const root = document.querySelector('[data-recipes]');
    if (!root || root.dataset.recipesReady === 'true') {
        return;
    }
    root.dataset.recipesReady = 'true';

    const tabs = root.querySelectorAll('[data-rc-tab]');
    const panels = {
        recepten: root.querySelector('[data-rc-panel="recepten"]'),
        aanbiedingen: root.querySelector('[data-rc-panel="aanbiedingen"]'),
    };
    const overview = root.querySelector('[data-rc-overview]');
    const detail = root.querySelector('[data-rc-detail]');

    /* ---------------- detail ---------------- */
    const closeDetail = () => {
        if (!detail) return;
        detail.hidden = true;
        detail.innerHTML = '';
        if (overview) overview.hidden = false;
    };

    const wireDetail = () => {
        detail.querySelector('[data-rc-back]')?.addEventListener('click', closeDetail);

        const list = detail.querySelector('[data-rc-shop-list]');
        const countEl = detail.querySelector('[data-rc-shop-count]');
        const doneEl = detail.querySelector('[data-rc-shop-done]');
        const clearBtn = detail.querySelector('[data-rc-shop-clear]');
        const rows = detail.querySelectorAll('[data-rc-shop]');
        const total = rows.length;

        const refresh = () => {
            const done = detail.querySelectorAll('.rc-shop-row.done').length;
            if (countEl) countEl.textContent = `${done}/${total}`;
            if (doneEl) doneEl.textContent = String(done);
            if (clearBtn) clearBtn.disabled = done === 0;
        };

        rows.forEach((row) =>
            row.addEventListener('click', () => {
                row.classList.toggle('done');
                refresh();
            })
        );

        clearBtn?.addEventListener('click', () => {
            rows.forEach((row) => row.classList.remove('done'));
            refresh();
        });
    };

    const openDetail = (recipe) => {
        if (!detail) return;
        detail.innerHTML = renderDetail(recipe);
        detail.hidden = false;
        if (overview) overview.hidden = true;
        wireDetail();
        root.scrollIntoView({ block: 'start' });
    };

    root.querySelectorAll('[data-rc-recipe]').forEach((card) => {
        card.addEventListener('click', () => {
            const payload = card.querySelector('[data-rc-recipe-data]');
            if (!payload) return;
            try {
                openDetail(JSON.parse(payload.textContent));
            } catch (e) {
                /* malformed payload — ignore */
            }
        });
    });

    /* ---------------- tabs ---------------- */
    const setTab = (tab) => {
        closeDetail();
        tabs.forEach((t) => t.classList.toggle('on', t.dataset.rcTab === tab));
        if (panels.recepten) panels.recepten.hidden = tab !== 'recepten';
        if (panels.aanbiedingen) panels.aanbiedingen.hidden = tab !== 'aanbiedingen';
    };

    tabs.forEach((t) => t.addEventListener('click', () => setTab(t.dataset.rcTab)));

    /* ---------------- opnieuw genereren ---------------- */
    root.querySelectorAll('[data-rc-generate]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            root.querySelectorAll('[data-rc-generate]').forEach((b) => (b.disabled = true));
            root.querySelector('[data-rc-gen]')?.removeAttribute('hidden');
            await post(root.dataset.generateUrl, { week_key: root.dataset.weekKey, refetch: true }).catch(() => {});
            window.location.reload();
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRecipes, { once: true });
} else {
    initRecipes();
}
document.addEventListener('livewire:navigated', initRecipes);
