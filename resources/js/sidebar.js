const COOKIE = 'sidebar_state';
const DEFAULT_STATE = 'expanded';

const TOGGLE = { expanded: 'rail', rail: 'expanded' };

function currentState() {
    return document.body.dataset.sidebar || DEFAULT_STATE;
}

function setSidebarState(state) {
    document.body.dataset.sidebar = state;
    document.cookie = `${COOKIE}=${state};path=/;max-age=31536000;samesite=lax`;
}

// wire:navigate replaces the whole <body>, so the dataset guard prevents double-wiring on navigation.
function wire(id, nextState) {
    const el = document.getElementById(id);
    if (!el || el.dataset.booted === 'true') {
        return;
    }
    el.dataset.booted = 'true';
    el.addEventListener('click', () => setSidebarState(nextState()));
}

export function bootSidebar() {
    wire('sidebar-toggle', () => TOGGLE[currentState()] ?? 'rail');
    wire('sidebar-hide', () => 'hidden');
    wire('sidebar-fab', () => 'expanded');
}
