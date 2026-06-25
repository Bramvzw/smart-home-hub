/* Dealtracker — vanilla interactions for the prijs-watchlist module.
   Server-rendered Blade; this layer opens the add-product flow (POST products
   → render returned candidate listings per retailer for review), confirms a
   candidate (POST listings/{id}/confirm), removes a candidate/listing
   (DELETE listings/{id}), runs "nu checken" (POST check then reload), and
   draws price-history sparklines from the history endpoint. */

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
const del = (url) => send(url, 'DELETE');
const get = (url) => send(url, 'GET');

const euro = (n) =>
    '€' + Number(n).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const esc = (s) =>
    String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

/* URL-scheme guard: retailer-supplied url/image_url is untrusted, so only
   http(s) links may ever reach an href/src. Anything else (javascript:, data:,
   relative/garbage) resolves to null and is dropped. */
export const safeHttpUrl = (value) => {
    if (typeof value !== 'string') return null;
    const trimmed = value.trim();
    if (trimmed === '') return null;
    try {
        const parsed = new URL(trimmed);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:' ? trimmed : null;
    } catch {
        return null;
    }
};

const STORE_META = {
    bol: { label: 'bol.com', cls: 'bol' },
    amazon: { label: 'Amazon', cls: 'amazon' },
    tweakers: { label: 'Tweakers', cls: 'tweakers' },
};
const storeLabel = (key) => STORE_META[String(key).toLowerCase()]?.label ?? String(key);

/* line-icon factory mirroring the Blade $dtIc closure (only the icons the
   client-rendered review + sparkline need). */
const DT_IC = {
    Search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/>',
    Check: '<path d="M5 12.5 10 17l9-10"/>',
    X: '<path d="M6 6l12 12M18 6 6 18"/>',
    Shield: '<path d="M12 3 5 6v5c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="M9.2 12l2 2 3.6-3.8"/>',
    Box: '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
};
const icon = (name, size = 16, stroke = 1.7, cls = '') => {
    const inner = DT_IC[name] || DT_IC.Box;
    return `<svg class="${cls}" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${stroke}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
};

const fillTpl = (tpl, id) => String(tpl || '').replace('__ID__', encodeURIComponent(id));

/* ---------------- sparkline ---------------- */
const drawSpark = (svg, data, trend = 'flat') => {
    const w = svg.viewBox.baseVal.width || 88;
    const h = svg.viewBox.baseVal.height || 30;
    if (!data || data.length < 2) {
        svg.innerHTML = '';
        return;
    }
    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min || 1;
    const pad = 3;
    const pts = data.map((v, i) => {
        const x = pad + (i / (data.length - 1)) * (w - pad * 2);
        const y = h - pad - ((v - min) / range) * (h - pad * 2);
        return [x, y];
    });
    const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    const area = `${line} L ${pts[pts.length - 1][0].toFixed(1)} ${h} L ${pts[0][0].toFixed(1)} ${h} Z`;
    const stroke =
        trend === 'down' ? 'var(--ok)' : trend === 'up' ? 'var(--danger)' : 'var(--tx-3)';
    const last = pts[pts.length - 1];
    const gid = 'dtg-' + Math.round(pts.reduce((a, p) => a + p[1], 0));
    svg.innerHTML = `<defs><linearGradient id="${gid}" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="${stroke}" stop-opacity="0.18"/>
        <stop offset="1" stop-color="${stroke}" stop-opacity="0"/></linearGradient></defs>
        <path d="${area}" fill="url(#${gid})"/>
        <path d="${line}" fill="none" stroke="${stroke}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="${last[0].toFixed(1)}" cy="${last[1].toFixed(1)}" r="2.4" fill="${stroke}"/>`;
};

const loadSparklines = (root) => {
    const tpl = root.dataset.historyTpl;
    root.querySelectorAll('[data-deals-product]').forEach((card) => {
        const productId = card.dataset.dealsProduct;
        const sparks = card.querySelectorAll('[data-deals-spark]');
        if (!productId || !tpl || sparks.length === 0) return;

        get(fillTpl(tpl, productId))
            .then((r) => (r.ok ? r.json() : null))
            .then((data) => {
                if (!data || !Array.isArray(data.listings)) return;
                const byListing = new Map(data.listings.map((l) => [String(l.id), l]));
                card.querySelectorAll('[data-deals-listing]').forEach((row) => {
                    const svg = row.querySelector('[data-deals-spark]');
                    const entry = byListing.get(String(row.dataset.dealsListing));
                    if (!svg || !entry || !Array.isArray(entry.price_points)) return;
                    const prices = entry.price_points
                        .slice()
                        .sort((a, b) => new Date(a.observed_at) - new Date(b.observed_at))
                        .map((p) => Number(p.price));
                    if (prices.length < 2) return;
                    const trend =
                        prices[prices.length - 1] < prices[0]
                            ? 'down'
                            : prices[prices.length - 1] > prices[0]
                              ? 'up'
                              : 'flat';
                    drawSpark(svg, prices, trend);
                });
            })
            .catch(() => {});
    });
};

/* ---------------- review rendering (after add) ---------------- */
const candidateCard = (listing) => {
    const imageUrl = safeHttpUrl(listing.image_url);
    const url = safeHttpUrl(listing.url);
    const name = url
        ? `<a class="dt-cand-name" href="${esc(url)}" target="_blank" rel="noopener noreferrer">${esc(listing.title)}</a>`
        : `<div class="dt-cand-name">${esc(listing.title)}</div>`;
    const thumb = imageUrl
        ? `<img src="${esc(imageUrl)}" alt="${esc(listing.title)}" loading="lazy">`
        : icon('Box', 20, 1.5, 'ic');

    return `<div class="dt-cand" data-deals-cand="${esc(listing.id)}">
    <div class="dt-cand-top">
        <div class="dt-cand-thumb">${thumb}</div>
        <div class="dt-cand-main">
            ${name}
            <div class="dt-cand-row">
                ${listing.current_price != null ? `<span class="dt-cand-price tnum">${euro(listing.current_price)}</span>` : ''}
            </div>
        </div>
    </div>
    <div class="dt-cand-actions">
        <button class="dt-cact confirm" data-deals-confirm>${icon('Check', 14, 1.7)} Bevestigen</button>
        <button class="dt-cact remove" data-deals-remove>${icon('X', 14, 1.7)} Verwijderen</button>
    </div>
</div>`;
};

export const renderReview = (product) => {
    const listings = Array.isArray(product.listings) ? product.listings : [];
    const cols = ['bol', 'amazon', 'tweakers']
        .map((store) => {
            const cands = listings.filter((l) => String(l.retailer).toLowerCase() === store);
            const body =
                cands.length === 0
                    ? `<div class="dt-cand-none">${icon('X', 20, 1.7, 'ic')}<div>Geen match — hier wordt niets gevolgd.</div></div>`
                    : cands.map(candidateCard).join('');
            const countLabel = cands.length === 1 ? 'kandidaat' : 'kandidaten';
            return `<div class="dt-storecol">
                <div class="dt-storecol-head ${store}">
                    <span class="led"></span>
                    <span class="dt-storecol-name">${esc(storeLabel(store))}</span>
                    <span class="dt-storecol-count">${cands.length} ${countLabel}</span>
                </div>
                <div class="dt-storecol-body">${body}</div>
            </div>`;
        })
        .join('');

    return `<div class="dt-add" data-deals-review="${esc(product.id)}">
        <div class="dt-review-head">
            <div class="dt-review-q">
                <span>Resultaten voor</span>
                <span class="term">${icon('Search', 13, 1.7, 'ic')} ${esc(product.name)}</span>
            </div>
        </div>
        <div class="dt-guard">
            ${icon('Shield', 17, 1.7, 'ic')}
            <div class="dt-guard-tx">
                Bevestig per winkel de <b>juiste</b> match en verwijder verkeerde resultaten — zoals een andere
                generatie of los accessoire. <b>Alleen bevestigde producten worden gevolgd</b>, zodat je geen
                verkeerde prijs binnenhaalt.
            </div>
        </div>
        <div class="dt-review-grid">${cols}</div>
    </div>`;
};

/* ---------------- candidate confirm / remove wiring ---------------- */
const wireCandidate = (root, card) => {
    const id = card.dataset.dealsCand;
    if (!id) return;

    card.querySelector('[data-deals-confirm]')?.addEventListener('click', async () => {
        card.classList.add('confirmed');
        await post(fillTpl(root.dataset.confirmTpl, id)).catch(() => {});
        window.location.reload();
    });

    card.querySelector('[data-deals-remove]')?.addEventListener('click', async () => {
        card.classList.add('removed');
        await del(fillTpl(root.dataset.destroyTpl, id)).catch(() => {});
        const col = card.closest('.dt-storecol-body');
        card.remove();
        if (col && col.querySelectorAll('[data-deals-cand]').length === 0) {
            col.innerHTML = `<div class="dt-cand-none">${icon('X', 20, 1.7, 'ic')}<div>Geen match — hier wordt niets gevolgd.</div></div>`;
        }
    });
};

export const initDeals = () => {
    const root = document.querySelector('[data-deals]');
    if (!root || root.dataset.dealsReady === 'true') {
        return;
    }
    root.dataset.dealsReady = 'true';

    const addPanel = root.querySelector('[data-deals-add]');
    const loadingPanel = root.querySelector('[data-deals-add-loading]');
    const main = root.querySelector('[data-deals-main]');
    const searchInput = root.querySelector('[data-deals-search-input]');
    const searchSubmit = root.querySelector('[data-deals-search-submit]');
    const searchTerm = root.querySelector('[data-deals-search-term]');

    const showAdd = () => {
        if (addPanel) addPanel.hidden = false;
        if (loadingPanel) loadingPanel.hidden = true;
        if (main) main.hidden = true;
        searchInput?.focus();
    };
    const showMain = () => {
        if (addPanel) addPanel.hidden = true;
        if (loadingPanel) loadingPanel.hidden = true;
        if (main) main.hidden = false;
    };

    /* ---------------- open / cancel add ---------------- */
    root.querySelectorAll('[data-deals-add-open]').forEach((b) => b.addEventListener('click', showAdd));
    root.querySelector('[data-deals-add-cancel]')?.addEventListener('click', showMain);

    searchInput?.addEventListener('input', () => {
        if (searchSubmit) searchSubmit.disabled = searchInput.value.trim() === '';
    });

    /* ---------------- add product → review ---------------- */
    root.querySelector('[data-deals-search-form]')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = (searchInput?.value || '').trim();
        if (!name) return;

        if (searchTerm) searchTerm.textContent = name;
        if (addPanel) addPanel.hidden = true;
        if (loadingPanel) loadingPanel.hidden = false;

        try {
            const res = await post(root.dataset.storeUrl, { name });
            if (!res.ok) throw new Error('store failed');
            const payload = await res.json();
            const product = payload.product;
            if (loadingPanel) loadingPanel.hidden = true;
            if (main) {
                main.hidden = false;
                main.insertAdjacentHTML('afterbegin', renderReview(product));
                main.querySelectorAll('[data-deals-cand]').forEach((c) => wireCandidate(root, c));
            }
        } catch {
            window.location.reload();
        }
    });

    /* ---------------- server-rendered candidates ---------------- */
    root.querySelectorAll('[data-deals-cand]').forEach((c) => wireCandidate(root, c));

    /* ---------------- nu checken ---------------- */
    root.querySelectorAll('[data-deals-check]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            root.querySelectorAll('[data-deals-check]').forEach((b) => (b.disabled = true));
            btn.querySelector('.ic')?.classList.add('spin');
            await post(root.dataset.checkUrl).catch(() => {});
            window.location.reload();
        });
    });

    /* ---------------- sparklines ---------------- */
    loadSparklines(root);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeals, { once: true });
} else {
    initDeals();
}
document.addEventListener('livewire:navigated', initDeals);
