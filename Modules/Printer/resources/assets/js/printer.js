/* 3D-printer voorraad — vanilla interactions for the Filament + Onderdelen
   inventories. Server-rendered Blade; this layer wires the real endpoints. */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const request = (url, method, body = null) =>
    fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body !== null ? JSON.stringify(body) : null,
    });

const fmt = (n) => new Intl.NumberFormat('nl-NL').format(n);

const HEX_PRESETS = ['#15171e', '#f4f3ee', '#e0a44e', '#e0575c', '#4eb0e6', '#54b896', '#8b6cd6', '#c9613f'];

const initPrinter = () => {
    const root = document.querySelector('[data-printer]');
    if (!root || root.dataset.printerReady === 'true') {
        return;
    }
    root.dataset.printerReady = 'true';

    const lowPct = (parseInt(root.dataset.lowPct, 10) || 20) / 100;

    /* ---------------- tabs ---------------- */
    const tabs = root.querySelectorAll('[data-pr-tab]');
    const panels = {
        filament: root.querySelector('[data-pr-panel="filament"]'),
        onderdelen: root.querySelector('[data-pr-panel="onderdelen"]'),
    };
    const subFilament = root.querySelector('[data-pr-sub-filament]');
    const subOnderdelen = root.querySelector('[data-pr-sub-onderdelen]');
    const title = root.querySelector('[data-pr-title]');
    const addBtn = root.querySelector('[data-pr-add]');
    const addLabel = root.querySelector('[data-pr-add-label]');

    let currentTab = 'filament';

    const setTab = (tab) => {
        currentTab = tab;
        tabs.forEach((t) => t.classList.toggle('on', t.dataset.prTab === tab));
        panels.filament.hidden = tab !== 'filament';
        panels.onderdelen.hidden = tab !== 'onderdelen';
        const onFil = tab === 'filament';
        if (title) title.textContent = onFil ? 'Filament' : 'Onderdelen';
        if (subFilament) subFilament.hidden = !onFil;
        if (subOnderdelen) subOnderdelen.hidden = onFil;
        if (addLabel) addLabel.textContent = onFil ? 'Nieuwe spoel' : 'Nieuw onderdeel';
    };

    tabs.forEach((t) => t.addEventListener('click', () => setTab(t.dataset.prTab)));

    /* ---------------- kebab menus ---------------- */
    const closeAllMenus = () => root.querySelectorAll('[data-pr-menu]').forEach((m) => (m.hidden = true));

    root.querySelectorAll('[data-pr-menuwrap]').forEach((wrap) => {
        const kebab = wrap.querySelector('[data-pr-kebab]');
        const menu = wrap.querySelector('[data-pr-menu]');
        kebab?.addEventListener('click', (e) => {
            e.stopPropagation();
            const wasOpen = !menu.hidden;
            closeAllMenus();
            menu.hidden = wasOpen;
        });
    });

    document.addEventListener('pointerdown', (e) => {
        if (!e.target.closest('[data-pr-menuwrap]')) closeAllMenus();
    });

    /* ---------------- inline grams adjuster ---------------- */
    root.querySelectorAll('[data-pr-spool]').forEach((card) => {
        const total = parseInt(card.dataset.prTotal, 10) || 0;
        const adj = card.querySelector('[data-pr-adj]');
        const foot = card.querySelector('[data-pr-ffoot]');
        const bar = card.querySelector('[data-pr-bar]');
        const remainingText = card.querySelector('[data-pr-remaining-text]');
        const adjRemaining = card.querySelector('[data-pr-adj-remaining]');
        const stockMeta = card.querySelector('[data-pr-stockmeta]');
        const adjustUrl = card.dataset.prAdjustUrl;

        const openAdj = () => {
            closeAllMenus();
            if (adj) adj.hidden = false;
            if (foot) foot.hidden = true;
        };
        const closeAdj = () => {
            if (adj) adj.hidden = true;
            if (foot) foot.hidden = false;
        };

        card.querySelectorAll('[data-pr-adjust-open]').forEach((b) => b.addEventListener('click', openAdj));
        card.querySelector('[data-pr-adjust-close]')?.addEventListener('click', closeAdj);

        const render = (remaining) => {
            card.dataset.prRemaining = String(remaining);
            const pct = total > 0 ? Math.round((remaining / total) * 100) : 0;
            const low = remaining <= total * lowPct;
            if (bar) bar.style.width = Math.max(pct, remaining > 0 ? 2 : 0) + '%';
            if (remainingText) remainingText.textContent = fmt(remaining);
            if (adjRemaining) adjRemaining.textContent = fmt(remaining);
            card.classList.toggle('low', low);
            if (stockMeta) {
                stockMeta.innerHTML = low
                    ? `<span class="pr-lowpill"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/></svg> Bijna op · ${pct}%</span>`
                    : `<span class="pr-pct tnum">${pct}%</span>`;
            }
        };

        const sendDelta = (delta) => {
            const current = parseInt(card.dataset.prRemaining, 10) || 0;
            const next = Math.max(0, Math.min(total, current + delta));
            const applied = next - current;
            if (applied === 0) return;
            render(next);
            request(adjustUrl, 'POST', { delta_g: applied }).catch(() => render(current));
        };

        adj?.querySelectorAll('[data-pr-delta]').forEach((chip) =>
            chip.addEventListener('click', () => sendDelta(parseInt(chip.dataset.prDelta, 10)))
        );
        adj?.querySelector('[data-pr-fill]')?.addEventListener('click', () => {
            const current = parseInt(card.dataset.prRemaining, 10) || 0;
            sendDelta(total - current);
        });
    });

    /* ---------------- part steppers ---------------- */
    root.querySelectorAll('[data-pr-part]').forEach((row) => {
        const step = parseFloat(row.dataset.prStep) || 1;
        const min = row.dataset.prMin !== '' ? parseFloat(row.dataset.prMin) : null;
        const countText = row.querySelector('[data-pr-count-text]');
        const minus = row.querySelector('[data-pr-step-minus]');
        const lowTag = row.querySelector('[data-pr-lowtag]');
        const pico = row.querySelector('.pr-pico');
        const adjustUrl = row.dataset.prAdjustUrl;

        const render = (count) => {
            row.dataset.prCount = String(count);
            if (countText) countText.textContent = fmt(count);
            if (minus) minus.disabled = count <= 0;
            const low = min !== null && count <= min;
            row.classList.toggle('low', low);
            if (lowTag) {
                lowTag.innerHTML = low
                    ? `<span class="pr-lowtag"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/></svg> Laag</span>`
                    : '';
            }
        };

        const sendDelta = (delta) => {
            const current = parseFloat(row.dataset.prCount) || 0;
            const next = Math.max(0, current + delta);
            const applied = next - current;
            if (applied === 0) return;
            render(next);
            request(adjustUrl, 'POST', { delta: applied }).catch(() => render(current));
        };

        row.querySelector('[data-pr-step-minus]')?.addEventListener('click', () => sendDelta(-step));
        row.querySelector('[data-pr-step-plus]')?.addEventListener('click', () => sendDelta(step));
    });

    /* ---------------- modals ---------------- */
    const filamentModal = root.querySelector('[data-pr-modal="filament"]');
    const partModal = root.querySelector('[data-pr-modal="part"]');

    const openModal = (modal) => {
        closeAllMenus();
        modal.hidden = false;
    };
    const closeModal = (modal) => {
        modal.hidden = true;
    };

    [filamentModal, partModal].forEach((modal) => {
        modal.querySelectorAll('[data-pr-modal-close]').forEach((b) =>
            b.addEventListener('click', () => closeModal(modal))
        );
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    /* ---- segmented controls ---- */
    const wireSegment = (modal, name, hidden) => {
        modal.querySelectorAll(`[data-pr-seg="${name}"] [data-pr-seg-val]`).forEach((btn) => {
            btn.addEventListener('click', () => {
                modal.querySelectorAll(`[data-pr-seg="${name}"] [data-pr-seg-val]`).forEach((b) =>
                    b.classList.toggle('on', b === btn)
                );
                hidden.value = btn.dataset.prSegVal;
                hidden.dispatchEvent(new Event('input'));
            });
        });
    };

    const fField = (k) => filamentModal.querySelector(`[data-pr-field="${k}"]`);
    const pField = (k) => partModal.querySelector(`[data-pr-field="${k}"]`);

    wireSegment(filamentModal, 'material', fField('material'));
    wireSegment(partModal, 'group', pField('group'));

    const setSegment = (modal, name, value) => {
        modal.querySelectorAll(`[data-pr-seg="${name}"] [data-pr-seg-val]`).forEach((b) =>
            b.classList.toggle('on', b.dataset.prSegVal === value)
        );
    };

    /* ---- filament: hex picker + live preview ---- */
    const prevSwatch = filamentModal.querySelector('[data-pr-prev-swatch]');
    const prevName = filamentModal.querySelector('[data-pr-prev-name]');
    const prevSub = filamentModal.querySelector('[data-pr-prev-sub]');
    const prevBar = filamentModal.querySelector('[data-pr-prev-bar]');
    const hexPreview = filamentModal.querySelector('[data-pr-hex-preview]');

    const renderPreview = () => {
        const hex = fField('hex').value || '#333';
        const material = fField('material').value;
        const color = fField('color').value;
        const brand = fField('brand').value;
        const total = parseInt(fField('total').value, 10) || 0;
        const remaining = parseInt(fField('remaining').value, 10) || 0;
        const pct = total > 0 ? Math.round((remaining / total) * 100) : 0;
        if (prevSwatch) prevSwatch.style.background = hex;
        if (hexPreview) hexPreview.style.background = hex;
        if (prevName) prevName.textContent = material + (color ? ' · ' + color : '');
        if (prevSub) prevSub.textContent = `${brand || 'Merk —'} · ${fmt(remaining)} / ${fmt(total)} g · ${pct}%`;
        if (prevBar) {
            prevBar.style.width = Math.max(pct, 2) + '%';
            prevBar.style.background = pct <= (lowPct * 100) ? 'var(--danger)' : 'var(--accent)';
        }
        filamentModal.querySelectorAll('[data-pr-hex-val]').forEach((b) =>
            b.classList.toggle('on', b.dataset.prHexVal.toLowerCase() === (fField('hex').value || '').toLowerCase())
        );
    };

    ['hex', 'color', 'brand', 'total', 'remaining', 'material'].forEach((k) =>
        fField(k).addEventListener('input', renderPreview)
    );
    filamentModal.querySelectorAll('[data-pr-hex-val]').forEach((b) =>
        b.addEventListener('click', () => {
            fField('hex').value = b.dataset.prHexVal;
            renderPreview();
        })
    );

    /* ---- part: min suffix mirrors unit ---- */
    const minSuffix = partModal.querySelector('[data-pr-min-suffix]');
    pField('unit').addEventListener('change', () => {
        if (minSuffix) minSuffix.textContent = pField('unit').value;
    });

    /* ---- modal state ---- */
    const filamentForm = filamentModal.querySelector('[data-pr-filament-form]');
    const partForm = partModal.querySelector('[data-pr-part-form]');
    let editTarget = null; // { type, url, deleteUrl }

    const resetFilamentForm = () => {
        fField('material').value = 'PLA';
        setSegment(filamentModal, 'material', 'PLA');
        fField('color').value = '';
        fField('brand').value = '';
        fField('hex').value = '#15171e';
        fField('total').value = 1000;
        fField('remaining').value = 1000;
        fField('price').value = '';
        fField('store').value = '';
        fField('bought').value = '';
        renderPreview();
    };

    const resetPartForm = () => {
        pField('name').value = '';
        pField('note').value = '';
        pField('group').value = 'reserve';
        setSegment(partModal, 'group', 'reserve');
        pField('count').value = 1;
        pField('unit').value = 'stuks';
        pField('min').value = 1;
        if (minSuffix) minSuffix.textContent = 'stuks';
    };

    const setFilamentMode = (editing) => {
        filamentModal.querySelector('[data-pr-modal-title]').textContent = editing ? 'Spoel bewerken' : 'Nieuwe spoel';
        filamentModal.querySelector('[data-pr-modal-desc]').textContent = editing
            ? 'Pas materiaal, kleur of voorraad aan'
            : 'Voeg een filamentspoel toe aan je voorraad';
        filamentModal.querySelector('[data-pr-submit-label]').textContent = editing ? 'Opslaan' : 'Spoel toevoegen';
        filamentModal.querySelector('[data-pr-modal-delete]').hidden = !editing;
    };

    const setPartMode = (editing) => {
        partModal.querySelector('[data-pr-modal-title]').textContent = editing ? 'Onderdeel bewerken' : 'Nieuw onderdeel';
        partModal.querySelector('[data-pr-modal-desc]').textContent = editing
            ? 'Pas naam, groep of aantal aan'
            : 'Voeg een reserveonderdeel of verbruiksartikel toe';
        partModal.querySelector('[data-pr-submit-label]').textContent = editing ? 'Opslaan' : 'Onderdeel toevoegen';
        partModal.querySelector('[data-pr-modal-delete]').hidden = !editing;
    };

    /* ---- add ----
       The header button follows the active tab; empty-state buttons carry an
       explicit data-pr-add-filament / data-pr-add-part marker. */
    const openAddFilament = () => {
        editTarget = null;
        resetFilamentForm();
        setFilamentMode(false);
        openModal(filamentModal);
    };
    const openAddPart = () => {
        editTarget = null;
        resetPartForm();
        setPartMode(false);
        openModal(partModal);
    };

    root.querySelectorAll('[data-pr-add]').forEach((btn) =>
        btn.addEventListener('click', () => {
            if (btn.hasAttribute('data-pr-add-part')) return openAddPart();
            if (btn.hasAttribute('data-pr-add-filament') && btn.closest('.pr-state')) return openAddFilament();
            // header button: follow the active tab
            return currentTab === 'filament' ? openAddFilament() : openAddPart();
        })
    );

    /* ---- edit spool ---- */
    root.querySelectorAll('[data-pr-spool]').forEach((card) => {
        card.querySelector('[data-pr-edit-spool]')?.addEventListener('click', () => {
            editTarget = { type: 'filament', url: card.dataset.prUpdateUrl, deleteUrl: card.dataset.prDeleteUrl, method: 'PATCH' };
            fField('material').value = card.dataset.prMaterial || 'PLA';
            setSegment(filamentModal, 'material', card.dataset.prMaterial || 'PLA');
            fField('color').value = card.dataset.prColor || '';
            fField('brand').value = card.dataset.prBrand || '';
            fField('hex').value = card.dataset.prHex || '#15171e';
            fField('total').value = card.dataset.prTotal || 0;
            fField('remaining').value = card.dataset.prRemaining || 0;
            fField('price').value = card.dataset.prPrice || '';
            fField('store').value = card.dataset.prStore || '';
            fField('bought').value = card.dataset.prBought || '';
            setFilamentMode(true);
            renderPreview();
            openModal(filamentModal);
        });

        card.querySelector('[data-pr-delete-spool]')?.addEventListener('click', () => {
            closeAllMenus();
            request(card.dataset.prDeleteUrl, 'DELETE')
                .then(() => window.location.reload())
                .catch(() => {});
        });
    });

    /* ---- edit part ---- */
    root.querySelectorAll('[data-pr-part]').forEach((row) => {
        row.querySelector('[data-pr-edit-part]')?.addEventListener('click', () => {
            editTarget = { type: 'part', url: row.dataset.prUpdateUrl, deleteUrl: row.dataset.prDeleteUrl, method: 'PATCH' };
            pField('name').value = row.dataset.prName || '';
            pField('note').value = row.dataset.prNote || '';
            pField('group').value = row.dataset.prGroup || 'reserve';
            setSegment(partModal, 'group', row.dataset.prGroup || 'reserve');
            pField('count').value = row.dataset.prCount || 0;
            pField('unit').value = row.dataset.prUnit || 'stuks';
            pField('min').value = row.dataset.prMin || '';
            if (minSuffix) minSuffix.textContent = row.dataset.prUnit || 'stuks';
            setPartMode(true);
            openModal(partModal);
        });
    });

    /* ---- delete from modal ---- */
    filamentModal.querySelector('[data-pr-modal-delete]')?.addEventListener('click', () => {
        if (!editTarget) return;
        request(editTarget.deleteUrl, 'DELETE').then(() => window.location.reload()).catch(() => {});
    });
    partModal.querySelector('[data-pr-modal-delete]')?.addEventListener('click', () => {
        if (!editTarget) return;
        request(editTarget.deleteUrl, 'DELETE').then(() => window.location.reload()).catch(() => {});
    });

    /* ---- submit: filament ---- */
    filamentForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const total = parseInt(fField('total').value, 10) || 0;
        let remaining = parseInt(fField('remaining').value, 10) || 0;
        remaining = Math.max(0, Math.min(total, remaining));
        const payload = {
            material: fField('material').value,
            color_name: fField('color').value,
            color_hex: fField('hex').value || null,
            brand: fField('brand').value || null,
            total_weight_g: total,
            remaining_g: remaining,
            purchase_price: fField('price').value !== '' ? parseFloat(fField('price').value) : null,
            purchase_store: fField('store').value || null,
            purchased_at: fField('bought').value || null,
        };
        if (!editTarget) payload.diameter_mm = 1.75;
        const url = editTarget ? editTarget.url : root.dataset.filamentStoreUrl;
        const method = editTarget ? 'PATCH' : 'POST';
        request(url, method, payload).then(() => window.location.reload()).catch(() => {});
    });

    /* ---- submit: part ---- */
    partForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const group = pField('group').value;
        const payload = {
            name: pField('name').value,
            notes: pField('note').value || null,
            category: group === 'verbruik' ? 'consumable' : 'spare',
            quantity: parseFloat(pField('count').value) || 0,
            unit: pField('unit').value || null,
            low_threshold: pField('min').value !== '' ? parseInt(pField('min').value, 10) : null,
        };
        const url = editTarget ? editTarget.url : root.dataset.partsStoreUrl;
        const method = editTarget ? 'PATCH' : 'POST';
        request(url, method, payload).then(() => window.location.reload()).catch(() => {});
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrinter, { once: true });
} else {
    initPrinter();
}
document.addEventListener('livewire:navigated', initPrinter);
