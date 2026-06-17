// Dashboard sidebar collapse behaviour. The state (expanded | rail | hidden) is
// mirrored server-side in App\Dashboard\SidebarState and persisted in an
// unencrypted cookie so navigation never flashes the wrong state.
const COOKIE = 'sidebar_state';
const DEFAULT_STATE = 'expanded';

// The chevron toggle only swaps between the full menu and the icon rail; the
// hide button and floating button jump straight to a fixed state.
const TOGGLE = { expanded: 'rail', rail: 'expanded' };

function currentState() {
    return document.body.dataset.sidebar || DEFAULT_STATE;
}

function setSidebarState(state) {
    document.body.dataset.sidebar = state;
    document.cookie = `${COOKIE}=${state};path=/;max-age=31536000;samesite=lax`;
}

// wire:navigate swaps the whole <body>, so controls are recreated on every
// navigation; the dataset guard keeps re-wiring idempotent.
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
