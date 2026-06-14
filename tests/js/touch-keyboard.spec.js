import { createTouchKeyboard } from '../../resources/js/touch-keyboard.js';

function focus(element) {
    element.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));
}

function press(key) {
    [...document.querySelectorAll('[data-touch-keyboard-key]')]
        .find((button) => button.dataset.touchKeyboardKey === key)
        .click();
}

describe('touch keyboard', () => {
    beforeEach(() => {
        document.body.className = '';
    });

    it('opens for text inputs and inserts typed keys', () => {
        document.body.innerHTML = '<input type="text" value="">';
        const input = document.querySelector('input');
        const inputHandler = jest.fn();
        input.addEventListener('input', inputHandler);

        createTouchKeyboard();
        focus(input);

        expect(document.querySelector('.touch-keyboard').classList.contains('is-open')).toBe(true);

        press('a');
        press('space');
        press('b');

        expect(input.value).toBe('a b');
        expect(inputHandler).toHaveBeenCalledTimes(3);
    });

    it('handles pointer input directly for touch screens', () => {
        document.body.innerHTML = '<input type="text" value="">';
        const input = document.querySelector('input');

        createTouchKeyboard();
        focus(input);

        document.querySelector('[data-touch-keyboard-key="a"]')
            .dispatchEvent(new Event('pointerdown', { bubbles: true, cancelable: true }));

        expect(input.value).toBe('a');
    });

    it('does not duplicate a key when pointer input is followed by click', () => {
        document.body.innerHTML = '<input type="text" value="">';
        const input = document.querySelector('input');

        createTouchKeyboard();
        focus(input);

        const key = document.querySelector('[data-touch-keyboard-key="a"]');
        key.dispatchEvent(new Event('pointerdown', { bubbles: true, cancelable: true }));
        key.click();

        expect(input.value).toBe('a');
    });

    it('supports shift and backspace', () => {
        document.body.innerHTML = '<input type="search" value="">';
        const input = document.querySelector('input');

        createTouchKeyboard();
        focus(input);

        press('shift');
        press('A');
        press('backspace');
        press('b');

        expect(input.value).toBe('b');
    });

    it('shows numbers only in symbol mode', () => {
        document.body.innerHTML = '<input type="text" value="">';

        createTouchKeyboard();
        focus(document.querySelector('input'));

        expect(document.querySelector('[data-touch-keyboard-key="1"]')).toBeNull();

        press('mode');

        expect(document.querySelector('[data-touch-keyboard-key="1"]')).not.toBeNull();
    });

    it('inserts escaped symbol keys', () => {
        document.body.innerHTML = '<input type="text" value="">';
        const input = document.querySelector('input');

        createTouchKeyboard();
        focus(input);

        press('mode');
        press('"');

        expect(input.value).toBe('"');
    });

    it('keeps typing when an input is replaced after input events', () => {
        document.body.innerHTML = '<div id="app"><input data-filter="search" type="text" value=""></div>';
        const app = document.getElementById('app');
        app.addEventListener('input', (event) => {
            app.innerHTML = `<input data-filter="search" type="text" value="${event.target.value}">`;
        });

        createTouchKeyboard();
        focus(document.querySelector('input'));

        press('a');
        press('b');

        expect(document.querySelector('input').value).toBe('ab');
    });

    it('ignores controls that are not text editing fields', () => {
        document.body.innerHTML = '<input type="range" value="50">';

        createTouchKeyboard();
        focus(document.querySelector('input'));

        expect(document.querySelector('.touch-keyboard').classList.contains('is-open')).toBe(false);
        expect(document.body.classList.contains('touch-keyboard-open')).toBe(false);
    });

    it('submits the active field form from the enter key', () => {
        document.body.innerHTML = '<form><input name="title" type="text"></form>';
        const form = document.querySelector('form');
        const submitHandler = jest.fn((event) => event.preventDefault());
        form.requestSubmit = () => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        form.addEventListener('submit', submitHandler);

        createTouchKeyboard();
        focus(document.querySelector('input'));
        press('enter');

        expect(submitHandler).toHaveBeenCalledTimes(1);
    });
});
