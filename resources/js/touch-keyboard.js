const TEXT_INPUT_TYPES = new Set([
    '',
    'email',
    'password',
    'search',
    'tel',
    'text',
    'url',
]);

const LETTER_ROWS = [
    ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
    ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
    ['z', 'x', 'c', 'v', 'b', 'n', 'm'],
];

const SYMBOL_ROWS = [
    ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
    ['-', '/', ':', ';', '(', ')', '€', '&', '@', '"'],
    ['.', ',', '?', '!', '\'', '#', '+'],
];

function isEditableElement(element) {
    if (! element || element.disabled || element.readOnly) return false;
    if (element.closest('[data-touch-keyboard="off"]')) return false;

    if (element instanceof HTMLTextAreaElement) return true;

    if (element instanceof HTMLInputElement) {
        return TEXT_INPUT_TYPES.has(element.type);
    }

    return element.isContentEditable === true;
}

function editableElements() {
    return [...document.querySelectorAll('input, textarea, [contenteditable]')]
        .filter(isEditableElement);
}

function sameDatasetValue(first, second, key) {
    return first.dataset?.[key] && first.dataset[key] === second.dataset?.[key];
}

function findReplacementTarget(target) {
    const candidates = editableElements();

    if (target.id) {
        const match = candidates.find((candidate) => candidate.id === target.id);
        if (match) return match;
    }

    for (const key of ['touchKeyboardId', 'filter', 'field', 'action']) {
        const match = candidates.find((candidate) => sameDatasetValue(target, candidate, key));
        if (match) return match;
    }

    if (target.name) {
        const match = candidates.find((candidate) => candidate.name === target.name);
        if (match) return match;
    }

    return null;
}

function restoreCaret(target) {
    if (! target || target.isContentEditable || typeof target.setSelectionRange !== 'function') return;

    const position = String(target.value ?? '').length;
    target.setSelectionRange(position, position);
}

function syncActiveTarget(target) {
    if (target.isConnected) return target;

    const replacement = findReplacementTarget(target);
    if (! replacement) return target;

    replacement.focus({ preventScroll: true });
    restoreCaret(replacement);

    return replacement;
}

function dispatchInput(target, data = null, inputType = 'insertText') {
    const event = typeof InputEvent === 'function'
        ? new InputEvent('input', { bubbles: true, inputType, data })
        : new Event('input', { bubbles: true });

    target.dispatchEvent(event);
}

function setSelectionValue(target, value, start, end) {
    target.value = value;

    if (typeof target.setSelectionRange === 'function') {
        target.setSelectionRange(start, end);
    }
}

function insertIntoField(target, text) {
    const value = target.value ?? '';
    const start = typeof target.selectionStart === 'number' ? target.selectionStart : value.length;
    const end = typeof target.selectionEnd === 'number' ? target.selectionEnd : value.length;

    if (typeof target.setRangeText === 'function') {
        target.setRangeText(text, start, end, 'end');
    } else {
        const nextValue = `${value.slice(0, start)}${text}${value.slice(end)}`;
        const nextCaret = start + text.length;
        setSelectionValue(target, nextValue, nextCaret, nextCaret);
    }

    dispatchInput(target, text);
}

function backspaceField(target) {
    const value = target.value ?? '';
    const start = typeof target.selectionStart === 'number' ? target.selectionStart : value.length;
    const end = typeof target.selectionEnd === 'number' ? target.selectionEnd : value.length;

    if (start !== end) {
        const nextValue = `${value.slice(0, start)}${value.slice(end)}`;
        setSelectionValue(target, nextValue, start, start);
        dispatchInput(target, null, 'deleteContentBackward');
        return;
    }

    if (start === 0) return;

    const nextValue = `${value.slice(0, start - 1)}${value.slice(end)}`;
    const nextCaret = start - 1;
    setSelectionValue(target, nextValue, nextCaret, nextCaret);
    dispatchInput(target, null, 'deleteContentBackward');
}

function insertIntoContentEditable(target, text) {
    target.focus();
    document.execCommand('insertText', false, text);
    dispatchInput(target, text);
}

function backspaceContentEditable(target) {
    target.focus();
    document.execCommand('delete', false);
    dispatchInput(target, null, 'deleteContentBackward');
}

function pressEnter(target) {
    const keydown = new KeyboardEvent('keydown', {
        bubbles: true,
        cancelable: true,
        key: 'Enter',
    });

    const shouldContinue = target.dispatchEvent(keydown);

    if (target instanceof HTMLTextAreaElement || target.isContentEditable) {
        insertText(target, '\n');
        return;
    }

    if (! shouldContinue) return;

    const form = target.form;
    if (! form) return;

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }
}

function insertText(target, text) {
    if (target.isContentEditable) {
        insertIntoContentEditable(target, text);
        return;
    }

    insertIntoField(target, text);
}

function backspace(target) {
    if (target.isContentEditable) {
        backspaceContentEditable(target);
        return;
    }

    backspaceField(target);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function keyButton(key, label = key, className = '') {
    const safeKey = escapeHtml(key);
    const safeLabel = escapeHtml(label);

    return `<button type="button" class="touch-key ${className}" data-touch-keyboard-key="${safeKey}" aria-label="${safeLabel}">
        <span>${safeLabel}</span>
    </button>`;
}

function keyRow(keys, className = '') {
    return `<div class="touch-keyboard-row ${className}" style="--touch-key-columns:${keys.length}">
        ${keys.join('')}
    </div>`;
}

function renderRows({ shifted, symbols }) {
    const rows = [];
    const sourceRows = symbols ? SYMBOL_ROWS : LETTER_ROWS;

    if (symbols) {
        rows.push(keyRow(
            SYMBOL_ROWS[0].map((key) => keyButton(key)),
            'touch-keyboard-number-row',
        ));
    }

    sourceRows.forEach((row, index) => {
        if (symbols && index === 0) return;

        const keys = row.map((key) => {
            const value = shifted && ! symbols ? key.toUpperCase() : key;
            return keyButton(value);
        });

        rows.push(keyRow(keys));
    });

    rows.push(`<div class="touch-keyboard-row touch-keyboard-command-row">
        ${keyButton('mode', symbols ? 'ABC' : '123', 'touch-key-utility')}
        ${keyButton('shift', 'Shift', `touch-key-utility ${shifted ? 'is-active' : ''}`)}
        ${keyButton('space', 'Spatie', 'touch-key-space')}
        ${keyButton('backspace', '⌫', 'touch-key-utility')}
        ${keyButton('enter', 'Enter', 'touch-key-utility touch-key-enter')}
        ${keyButton('hide', '×', 'touch-key-utility')}
    </div>`);

    return rows.join('');
}

// The document-level listeners are bound once for the lifetime of the page and
// always address the current keyboard via _activeKeyboard. wire:navigate swaps
// the <body>, so the keyboard element is recreated on every navigation; binding
// these listeners per-instance would otherwise stack them on `document`.
let _activeKeyboard = null;
let _documentListenersBound = false;

function bindKeyboardDocumentListeners() {
    if (_documentListenersBound) return;
    _documentListenersBound = true;

    document.addEventListener('focusin', (event) => {
        if (! isEditableElement(event.target)) return;
        _activeKeyboard?.show(event.target);
    });

    document.addEventListener('focusout', () => {
        window.setTimeout(() => {
            const keyboard = _activeKeyboard?.element;
            if (isEditableElement(document.activeElement) || keyboard?.contains(document.activeElement)) return;
            _activeKeyboard?.hide();
        }, 0);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && _activeKeyboard?.element.classList.contains('is-open')) {
            _activeKeyboard.hide();
        }
    });
}

export function createTouchKeyboard({ root = document.body } = {}) {
    let activeTarget = null;
    let shifted = false;
    let symbols = false;
    let suppressNextClick = false;

    const existing = document.querySelector('[data-touch-keyboard-root]');
    if (existing) {
        _activeKeyboard = existing.touchKeyboardApi;
        return existing.touchKeyboardApi;
    }

    const keyboard = document.createElement('section');
    keyboard.className = 'touch-keyboard';
    keyboard.dataset.touchKeyboardRoot = 'true';
    keyboard.setAttribute('aria-label', 'Schermtoetsenbord');
    keyboard.innerHTML = '<div class="touch-keyboard-inner"></div>';
    root.appendChild(keyboard);

    const inner = keyboard.querySelector('.touch-keyboard-inner');

    function render() {
        inner.innerHTML = renderRows({ shifted, symbols });
        keyboard.dataset.mode = symbols ? 'symbols' : 'letters';
    }

    function show(target) {
        activeTarget = target;
        render();
        keyboard.classList.add('is-open');
        document.body.classList.add('touch-keyboard-open');

        window.setTimeout(() => {
            if (typeof target.scrollIntoView === 'function') {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }, 80);
    }

    function hide() {
        activeTarget = null;
        keyboard.classList.remove('is-open');
        document.body.classList.remove('touch-keyboard-open');
    }

    function handleKey(key) {
        if (activeTarget && ! activeTarget.isConnected) {
            activeTarget = findReplacementTarget(activeTarget);
        }

        if (! activeTarget || ! isEditableElement(activeTarget)) {
            hide();
            return;
        }

        activeTarget.focus({ preventScroll: true });

        if (key === 'hide') {
            activeTarget.blur();
            hide();
            return;
        }

        if (key === 'mode') {
            symbols = ! symbols;
            shifted = false;
            render();
            return;
        }

        if (key === 'shift') {
            shifted = ! shifted;
            render();
            return;
        }

        if (key === 'space') {
            insertText(activeTarget, ' ');
            activeTarget = syncActiveTarget(activeTarget);
            return;
        }

        if (key === 'backspace') {
            backspace(activeTarget);
            activeTarget = syncActiveTarget(activeTarget);
            return;
        }

        if (key === 'enter') {
            pressEnter(activeTarget);
            activeTarget = syncActiveTarget(activeTarget);
            return;
        }

        insertText(activeTarget, key);
        activeTarget = syncActiveTarget(activeTarget);

        if (shifted && ! symbols) {
            shifted = false;
            render();
        }
    }

    keyboard.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        const key = event.target.closest('[data-touch-keyboard-key]')?.dataset.touchKeyboardKey;
        if (! key) return;

        handleKey(key);
        suppressNextClick = true;
    });

    keyboard.addEventListener('click', (event) => {
        if (suppressNextClick) {
            suppressNextClick = false;
            return;
        }

        const key = event.target.closest('[data-touch-keyboard-key]')?.dataset.touchKeyboardKey;
        if (key) handleKey(key);
    });

    const api = { element: keyboard, show, hide, isEditableElement };
    keyboard.touchKeyboardApi = api;
    _activeKeyboard = api;
    bindKeyboardDocumentListeners();
    render();

    return api;
}

export function initTouchKeyboard() {
    const boot = () => createTouchKeyboard();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }

    // wire:navigate swaps the whole <body>, removing the keyboard element.
    // Re-create it after each navigation (createTouchKeyboard is idempotent:
    // it returns the existing instance when one is still present).
    document.addEventListener('livewire:navigated', boot);
}
