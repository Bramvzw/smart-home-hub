import './bootstrap';
import { initTouchKeyboard } from './touch-keyboard.js';

initTouchKeyboard();

// Sidebar collapse persistence
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebar-toggle');

if (sidebar) {
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    toggleBtn?.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed') ? 'true' : 'false');
    });
}
