/* Entertainment & muziek — vanilla interactions for the module. Server-rendered
   Blade; this layer switches sections (Films · Concerten · Nieuwe muziek),
   sends film thumb-feedback and dismiss to their endpoints, hydrates and saves
   the smaakprofiel via the taste endpoint, and wires "Vernieuwen" to refresh. */

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
const put = (url, body = {}) => send(url, 'PUT', body);
const get = (url) => send(url, 'GET');

const initEntertainment = () => {
    const root = document.querySelector('[data-ent]');
    if (!root || root.dataset.entReady === 'true') {
        return;
    }
    root.dataset.entReady = 'true';

    /* ---------------- tabs / sections ---------------- */
    const tabs = root.querySelectorAll('[data-ent-tab]');
    const panels = root.querySelectorAll('[data-ent-panel]');
    const subs = root.querySelectorAll('[data-ent-sub]');

    const setTab = (tab) => {
        tabs.forEach((t) => t.classList.toggle('on', t.dataset.entTab === tab));
        panels.forEach((p) => (p.hidden = p.dataset.entPanel !== tab));
        subs.forEach((s) => (s.hidden = s.dataset.entSub !== tab));
    };

    tabs.forEach((t) => t.addEventListener('click', () => setTab(t.dataset.entTab)));

    /* ---------------- film feedback (duim op/neer) ---------------- */
    root.querySelectorAll('[data-ent-film]').forEach((film) => {
        const url = film.dataset.entFeedbackUrl;
        const thumbs = film.querySelectorAll('[data-ent-thumb]');

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const sentiment = thumb.dataset.entThumb;
                const wasOn = thumb.classList.contains('on');

                thumbs.forEach((t) => {
                    t.classList.remove('on');
                    t.setAttribute('aria-pressed', 'false');
                });

                if (!wasOn) {
                    thumb.classList.add('on');
                    thumb.setAttribute('aria-pressed', 'true');
                    if (url) {
                        post(url, { sentiment }).catch(() => {});
                    }
                }
            });
        });

        // film verbergen
        film.querySelector('[data-ent-dismiss]')?.addEventListener('click', async () => {
            const dismissUrl = film.dataset.entDismissUrl;
            if (dismissUrl) {
                await post(dismissUrl).catch(() => {});
            }
            film.remove();
        });
    });

    /* ---------------- smaakprofiel ---------------- */
    const taste = root.querySelector('[data-ent-taste]');
    if (taste) {
        const showUrl = taste.dataset.entTasteShowUrl;
        const saveUrl = taste.dataset.entTasteSaveUrl;
        const genreButtons = taste.querySelectorAll('[data-ent-genre]');
        const favList = taste.querySelector('[data-ent-fav-list]');
        const favAdd = taste.querySelector('[data-ent-fav-add]');

        let favorites = [];
        let notes = null;

        const esc = (s) =>
            String(s ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

        const star = `<svg class="ic" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color:var(--accent);"><path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 17l-5.2 2.7 1-5.9L3.5 9.7l5.9-.8z"/></svg>`;
        const cross = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>`;

        const activeGenres = () =>
            Array.from(genreButtons)
                .filter((b) => b.classList.contains('on'))
                .map((b) => b.dataset.entGenre);

        const save = () => {
            if (!saveUrl) return;
            put(saveUrl, { genres: activeGenres(), favorite_titles: favorites, notes }).catch(() => {});
        };

        const renderFavorites = () => {
            favList.innerHTML = favorites
                .map(
                    (f) => `<div class="ent-fav" data-ent-fav="${esc(f)}">
                        ${star}
                        <span class="ent-fav-name">${esc(f)}</span>
                        <button class="ent-fav-x" data-ent-fav-x aria-label="Verwijderen">${cross}</button>
                    </div>`
                )
                .join('');

            favList.querySelectorAll('[data-ent-fav-x]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('[data-ent-fav]');
                    const title = row?.dataset.entFav;
                    favorites = favorites.filter((x) => x !== title);
                    renderFavorites();
                    save();
                });
            });
        };

        genreButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                btn.classList.toggle('on');
                save();
            });
        });

        favAdd?.addEventListener('click', () => {
            const title = window.prompt('Welke film vond je goed?');
            const clean = (title ?? '').trim();
            if (!clean || favorites.includes(clean)) return;
            favorites.push(clean);
            renderFavorites();
            save();
        });

        // hydrate from the taste endpoint
        if (showUrl) {
            get(showUrl)
                .then((r) => (r.ok ? r.json() : null))
                .then((data) => {
                    if (!data) return;
                    const active = data.genres || [];
                    genreButtons.forEach((b) =>
                        b.classList.toggle('on', active.includes(b.dataset.entGenre))
                    );
                    favorites = Array.isArray(data.favorite_titles) ? data.favorite_titles : [];
                    notes = data.notes ?? null;
                    renderFavorites();
                })
                .catch(() => {});
        }
    }

    /* ---------------- vernieuwen ---------------- */
    const refreshUrl = root.dataset.refreshUrl;
    root.querySelectorAll('[data-ent-refresh]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            root.querySelectorAll('[data-ent-refresh]').forEach((b) => (b.disabled = true));
            btn.querySelector('.ic')?.classList.add('spin');
            if (refreshUrl) {
                await post(refreshUrl).catch(() => {});
            }
            window.location.reload();
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEntertainment, { once: true });
} else {
    initEntertainment();
}
document.addEventListener('livewire:navigated', initEntertainment);
