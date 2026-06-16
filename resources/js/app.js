import './bootstrap';
import { initTouchKeyboard } from './touch-keyboard.js';

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

// Sidebar state machine: expanded -> rail -> hidden.
//
// The state lives in body[data-sidebar] which is rendered server-side from an
// (unencrypted) cookie, so navigation never flashes the wrong state. wire:navigate
// swaps the whole <body>, so the toggle/fab elements are recreated on every
// navigation and must be re-wired; per-element dataset guards keep this idempotent.
function setSidebarState(state) {
    document.body.dataset.sidebar = state;
    // Plain (unencrypted) cookie so the server can read it back on the next render.
    document.cookie = `sidebar_state=${state};path=/;max-age=31536000;samesite=lax`;
}

function bootSidebar() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    const hideBtn = document.getElementById('sidebar-hide');
    const fabBtn = document.getElementById('sidebar-fab');

    if (toggleBtn && toggleBtn.dataset.booted !== 'true') {
        toggleBtn.dataset.booted = 'true';
        // Chevron only toggles between the full menu and the icon rail.
        toggleBtn.addEventListener('click', () => {
            const current = document.body.dataset.sidebar || 'expanded';
            setSidebarState(current === 'rail' ? 'expanded' : 'rail');
        });
    }

    if (hideBtn && hideBtn.dataset.booted !== 'true') {
        hideBtn.dataset.booted = 'true';
        // Dedicated button to hide the menu entirely (down to the floating button).
        hideBtn.addEventListener('click', () => setSidebarState('hidden'));
    }

    if (fabBtn && fabBtn.dataset.booted !== 'true') {
        fabBtn.dataset.booted = 'true';
        // The floating button only shows while hidden; bring the menu fully back.
        fabBtn.addEventListener('click', () => setSidebarState('expanded'));
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSidebar, { once: true });
} else {
    bootSidebar();
}
document.addEventListener('livewire:navigated', bootSidebar);
