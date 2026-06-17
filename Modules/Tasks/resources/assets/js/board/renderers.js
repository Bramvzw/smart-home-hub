import { labelColors } from './colors.js';
import { deadlineStatus, escapeHtml, formatDate } from './formatters.js';

const renderLabels = (labels) => labels.map((label) => {
    const color = labelColors[label.color] ?? labelColors.slate;
    return `<span class="task-label" style="--label-bg:${color.bg};--label-fg:${color.fg};--label-dot:${color.dot}">
        <span></span>${escapeHtml(label.name)}
    </span>`;
}).join('');

const renderCard = (task) => {
    const checklistDone = task.checklist.filter((item) => item.completed).length;
    const checklistTotal = task.checklist.length;
    const dateStatus = deadlineStatus(task.due_date);

    return `<article class="task-card ${task.completed ? 'is-completed' : ''} ${task.archived ? 'is-archived' : ''}" data-task-id="${task.id}">
        <span class="priority-bar priority-${task.priority}"></span>
        <div class="task-card-main">
            <button class="task-check" data-action="toggle-task" title="${task.completed ? 'Mark as open' : 'Mark as done'}">${task.completed ? '✓' : ''}</button>
            <h3>${escapeHtml(task.title)}</h3>
        </div>
        ${task.description ? `<p>${escapeHtml(task.description)}</p>` : ''}
        ${task.labels.length ? `<div class="task-labels">${renderLabels(task.labels)}</div>` : ''}
        ${(task.due_date || checklistTotal || task.archived) ? `<div class="task-meta">
            ${task.due_date ? `<span class="deadline ${dateStatus}">${formatDate(task.due_date)}</span>` : ''}
            ${checklistTotal ? `<span>${checklistDone}/${checklistTotal}</span>` : ''}
            ${task.archived ? '<span>Archived</span>' : ''}
        </div>` : ''}
    </article>`;
};

const renderSidebar = (store) => `<aside class="tasks-sidebar">
    <div class="tasks-brand">
        <div class="tasks-mark">T</div>
        <div>
            <strong>Smart Home Hub</strong>
            <span>Local tasks</span>
        </div>
    </div>
    <div class="sidebar-heading">
        <span>Boards</span>
        <button data-action="new-board" title="New board">+</button>
    </div>
    <nav class="board-list">
        ${store.state.boards.map((item) => `<button class="board-switch ${item.id === store.state.activeBoardId ? 'active' : ''}" data-board-id="${item.id}">
            <span>${escapeHtml(item.name)}</span>
            <small>${item.count}</small>
        </button>`).join('')}
    </nav>
</aside>`;

const renderToolbar = (store) => {
    const activeLabels = store.board().labels;
    const totalActive = store.allTasks().filter((task) => ! task.archived).length;
    const totalVisible = store.allTasks().filter(store.matchesFilters).length;
    const filters = store.filters;

    return `<header class="tasks-toolbar">
        <div class="toolbar-primary">
            <input class="board-title-input" value="${escapeHtml(store.board().name)}" data-action="rename-board" aria-label="Board name">
            <span>${totalVisible}/${totalActive} visible</span>
            <button class="toolbar-icon danger" data-action="delete-board" title="Delete board">Delete board</button>
            <button class="primary-action" data-action="new-task">+ New task</button>
        </div>
        <div class="toolbar-filters">
            <input class="search-input" placeholder="Search..." value="${escapeHtml(filters.search)}" data-filter="search">
            <select data-filter="label">
                <option value="">All labels</option>
                ${activeLabels.map((label) => `<option value="${label.id}" ${String(label.id) === filters.label ? 'selected' : ''}>${escapeHtml(label.name)}</option>`).join('')}
            </select>
            <select data-filter="priority">
                <option value="">All priorities</option>
                <option value="high" ${filters.priority === 'high' ? 'selected' : ''}>High</option>
                <option value="normal" ${filters.priority === 'normal' ? 'selected' : ''}>Normal</option>
                <option value="low" ${filters.priority === 'low' ? 'selected' : ''}>Low</option>
            </select>
            <select data-filter="deadline">
                <option value="">All due dates</option>
                <option value="overdue" ${filters.deadline === 'overdue' ? 'selected' : ''}>Overdue</option>
                <option value="today" ${filters.deadline === 'today' ? 'selected' : ''}>Today</option>
                <option value="week" ${filters.deadline === 'week' ? 'selected' : ''}>This week</option>
            </select>
            <label class="archive-toggle">
                <input type="checkbox" data-filter="showArchived" ${filters.showArchived ? 'checked' : ''}>
                Archive
            </label>
            <button class="clear-filters" data-action="clear-filters">Clear</button>
        </div>
    </header>`;
};

const renderColumns = (store) => `<main class="tasks-board" data-sortable-columns>
    ${store.board().columns.map((column) => {
        const visibleTasks = column.tasks.filter(store.matchesFilters);
        return `<section class="task-column ${column.name.toLowerCase() === 'done' ? 'done-column' : ''}" data-column-id="${column.id}">
            <header class="column-header">
                <span class="column-grip"></span>
                <input class="column-name-input" value="${escapeHtml(column.name)}" data-action="rename-column">
                <small>${visibleTasks.length}</small>
                <button data-action="delete-column" title="Delete column">×</button>
            </header>
            <div class="task-list" data-sortable-tasks data-column-id="${column.id}">
                ${visibleTasks.map(renderCard).join('')}
                ${visibleTasks.length ? '' : '<div class="column-empty">No tasks</div>'}
            </div>
            <form class="quick-add" data-action="quick-add">
                <input name="title" placeholder="Add task...">
                <button>+</button>
            </form>
        </section>`;
    }).join('')}
    <button class="add-column" data-action="new-column">+ New column</button>
</main>`;

const renderDetail = (store) => {
    const task = store.selectedTask();
    if (! task) return '<aside class="task-detail"></aside>';

    const selectedLabelIds = new Set(task.labels.map((label) => String(label.id)));
    const checklistDone = task.checklist.filter((item) => item.completed).length;
    const created = task.created_at ? formatDate(task.created_at) : '';

    return `<aside class="task-detail open">
        <div class="detail-head">
            <span class="priority-pill priority-${task.priority}">${task.priority === 'high' ? 'High' : task.priority === 'low' ? 'Low' : 'Normal'}</span>
            ${task.completed ? '<span class="done-pill">Done</span>' : ''}
            <button data-action="close-detail">×</button>
        </div>
        <div class="detail-body">
            <label class="detail-title">
                <button class="task-check large" data-action="toggle-task">${task.completed ? '✓' : ''}</button>
                <textarea data-field="title" rows="1">${escapeHtml(task.title)}</textarea>
            </label>
            <div class="field">
                <label>Priority</label>
                <div class="priority-segment">
                    ${['high', 'normal', 'low'].map((priority) => `<button class="${task.priority === priority ? 'active ' : ''}priority-${priority}" data-priority="${priority}">${priority === 'high' ? 'High' : priority === 'low' ? 'Low' : 'Normal'}</button>`).join('')}
                </div>
            </div>
            <div class="field">
                <label>Due date</label>
                <input type="date" data-field="due_date" value="${escapeHtml(task.due_date ?? '')}">
            </div>
            <div class="field">
                <label>Labels</label>
                <div class="label-pool">
                    ${store.board().labels.map((label) => {
                        const color = labelColors[label.color] ?? labelColors.slate;
                        return `<button class="${selectedLabelIds.has(String(label.id)) ? 'active' : ''}" data-label-id="${label.id}" style="--label-dot:${color.dot}">${escapeHtml(label.name)}</button>`;
                    }).join('')}
                </div>
                <form class="new-label" data-action="new-label">
                    <input name="name" placeholder="New label">
                    <select name="color">
                        ${Object.keys(labelColors).map((color) => `<option value="${color}">${color}</option>`).join('')}
                    </select>
                    <button>Create</button>
                </form>
            </div>
            <div class="field">
                <label>Description</label>
                <textarea class="description" data-field="description">${escapeHtml(task.description)}</textarea>
            </div>
            <div class="field">
                <label>Checklist ${task.checklist.length ? `<span>${checklistDone}/${task.checklist.length}</span>` : ''}</label>
                <div class="checklist">
                    ${task.checklist.map((item, index) => `<div class="checklist-item" data-checklist-index="${index}">
                        <button data-action="toggle-checklist">${item.completed ? '✓' : ''}</button>
                        <input value="${escapeHtml(item.text)}" data-action="edit-checklist" class="${item.completed ? 'done' : ''}">
                        <button class="muted-danger" data-action="delete-checklist">×</button>
                    </div>`).join('')}
                </div>
                <form class="new-checklist" data-action="new-checklist">
                    <input name="text" placeholder="Add item...">
                </form>
            </div>
        </div>
        <footer class="detail-foot">
            <button data-action="archive-task">${task.archived ? 'Restore' : 'Archive'}</button>
            <button class="danger" data-action="delete-task">Delete</button>
            <small>${created ? `Created ${created}` : ''}</small>
        </footer>
    </aside>`;
};

export const renderTasksApp = (store) => `<div class="tasks-shell ${store.selectedTask() ? 'detail-open' : ''}">
    ${renderSidebar(store)}
    <section class="tasks-main">
        ${renderToolbar(store)}
        <div class="board-scroll">${renderColumns(store)}</div>
    </section>
    ${renderDetail(store)}
</div>`;
