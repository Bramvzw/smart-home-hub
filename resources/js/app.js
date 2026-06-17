import './bootstrap';
import { initTouchKeyboard } from './touch-keyboard.js';
import { bootSidebar } from './sidebar.js';

initTouchKeyboard();

// Hide the mouse cursor on touch devices (the Pi kiosk) while keeping it on
// mouse-driven screens (e.g. the Mac). Chromium on the Pi reports its
// touchscreen as a mouse, so a CSS media query is unreliable; instead flag the
// device with .touch-device on the first real touch. The flag lives on <html>,
// which wire:navigate never swaps, so it is set once for the page lifetime.
function flagTouchDevice() {
    document.documentElement.classList.add('touch-device');
}

if (window.matchMedia?.('(pointer: coarse)').matches) {
    flagTouchDevice();
}

window.addEventListener('touchstart', flagTouchDevice, { once: true });

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSidebar, { once: true });
} else {
    bootSidebar();
}
document.addEventListener('livewire:navigated', bootSidebar);
