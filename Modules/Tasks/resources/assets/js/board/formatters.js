export const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

export const deadlineStatus = (date) => {
    if (! date) return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(`${date}T00:00:00`);
    const days = Math.round((target - today) / 86400000);
    if (days < 0) return 'overdue';
    if (days === 0) return 'today';
    if (days <= 7) return 'week';
    return '';
};

export const formatDate = (date) => {
    if (! date) return '';
    return new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short' }).format(new Date(`${date}T00:00:00`));
};
