const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const post = (url, body = {}) =>
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });

const updateCounts = (root) => {
    let total = 0;

    root.querySelectorAll('[data-news-topic]').forEach((topic) => {
        const unread = topic.querySelectorAll('.nw-item.unread').length;
        total += unread;

        const pill = topic.querySelector('[data-news-topic-pill]');
        if (pill) {
            pill.textContent = String(unread);
            pill.classList.toggle('zero', unread === 0);
        }

        const clear = topic.querySelector('[data-news-read-topic]');
        if (clear) {
            clear.disabled = unread === 0;
        }
    });

    const readAll = root.querySelector('[data-news-read-all]');
    if (readAll) {
        readAll.disabled = total === 0;
    }
};

const markItemRead = (item) => {
    item.classList.remove('unread');
    item.classList.add('read');
};

const initNews = () => {
    const root = document.querySelector('[data-news]');
    if (!root || root.dataset.newsReady === 'true') {
        return;
    }
    root.dataset.newsReady = 'true';

    // Open an item: mark read (optimistic), persist, open the source in a new tab.
    root.querySelectorAll('[data-news-item]').forEach((item) => {
        item.addEventListener('click', () => {
            const url = item.dataset.newsUrl;
            const readUrl = item.dataset.newsReadUrl;

            if (item.classList.contains('unread')) {
                markItemRead(item);
                updateCounts(root);
                post(readUrl).catch(() => {});
            }

            if (url) {
                window.open(url, '_blank', 'noopener');
            }
        });
    });

    // Mark a single topic as read.
    root.querySelectorAll('[data-news-read-topic]').forEach((button) => {
        button.addEventListener('click', async () => {
            const topic = button.dataset.newsReadTopic;
            button.disabled = true;
            await post(root.dataset.newsReadallUrl, { topic }).catch(() => {});
            window.location.reload();
        });
    });

    // Mark everything read.
    root.querySelector('[data-news-read-all]')?.addEventListener('click', async (event) => {
        event.currentTarget.disabled = true;
        await post(root.dataset.newsReadallUrl).catch(() => {});
        window.location.reload();
    });

    // Refresh the feeds.
    root.querySelectorAll('[data-news-refresh]').forEach((button) => {
        button.addEventListener('click', async () => {
            root.querySelectorAll('[data-news-refresh]').forEach((b) => (b.disabled = true));
            root.querySelector('[data-news-stamp]')?.classList.add('busy');
            await post(root.dataset.newsRefreshUrl).catch(() => {});
            window.location.reload();
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNews, { once: true });
} else {
    initNews();
}
document.addEventListener('livewire:navigated', initNews);
