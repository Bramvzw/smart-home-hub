import './bootstrap';
import { initTouchKeyboard } from './touch-keyboard.js';
import { bootSidebar } from './sidebar.js';

initTouchKeyboard();

// Chromium on the Pi reports its touchscreen as a mouse, so (pointer: coarse) alone is unreliable.
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
