const pingBtn = document.getElementById('ping-btn');
const stopBtn = document.getElementById('stop-btn');
const status = document.getElementById('ping-status');

const INTERVAL_MS = 5_000;
// Safety cap so a forgotten tab doesn't drain the ntfy quota; ~3 minutes.
const MAX_PINGS = 36;

let timer = null;
let sent = 0;

if (pingBtn && stopBtn) {
    pingBtn.addEventListener('click', start);
    stopBtn.addEventListener('click', () => stop('Stopped.'));
}

async function sendPing() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    try {
        const res = await fetch(pingBtn.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });

        if (res.ok) {
            sent += 1;
            status.textContent = `Pinging your phone… (${sent} sent)`;
        } else {
            const { message } = await res.json();
            stop(message || 'Ping failed.');
        }
    } catch {
        stop('Network error.');
    }
}

function start() {
    if (timer) {
        return;
    }
    sent = 0;
    pingBtn.hidden = true;
    stopBtn.hidden = false;

    sendPing();
    timer = setInterval(() => {
        if (sent >= MAX_PINGS) {
            stop('Stopped automatically after 3 minutes.');
            return;
        }
        sendPing();
    }, INTERVAL_MS);
}

function stop(message) {
    clearInterval(timer);
    timer = null;
    stopBtn.hidden = true;
    pingBtn.hidden = false;
    status.textContent = message;
}
