const btn = document.getElementById('ping-btn');
if (btn) {
    btn.addEventListener('click', async () => {
        const label = document.getElementById('ping-label');
        const status = document.getElementById('ping-status');

        btn.disabled = true;
        label.textContent = 'Sending…';
        status.textContent = '';

        try {
            const res = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': btn.dataset.csrf, 'Accept': 'application/json' },
            });
            const { message } = await res.json();

            if (res.ok) {
                label.textContent = 'Sent ✓';
                status.textContent = message;
            } else {
                label.textContent = 'Ping';
                status.textContent = message;
                btn.disabled = false;
            }
        } catch {
            label.textContent = 'Ping';
            status.textContent = 'Network error.';
            btn.disabled = false;
        }

        // Re-enable after cooldown so double-pings don't flood ntfy.
        if (btn.disabled) {
            setTimeout(() => {
                btn.disabled = false;
                label.textContent = 'Ping';
                status.textContent = '';
            }, 10_000);
        }
    });
}
