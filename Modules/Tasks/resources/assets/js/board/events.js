export function bindTaskEvents(root, store, api, render) {
    root.addEventListener('click', (event) => {
        const action = event.target.closest('[data-action]')?.dataset.action;
        const taskCard = event.target.closest('.task-card');
        const task = store.selectedTask();

        if (taskCard && ! action) {
            store.setSelectedTaskId(Number(taskCard.dataset.taskId));
            render();
            return;
        }

        if (! action) return;

        if (action === 'new-board') {
            const name = window.prompt('Board name', 'New board');
            if (name) api.request(api.routes.boardsStore, 'POST', { name });
        }

        if (action === 'delete-board') {
            if (window.confirm(`Delete board "${store.board().name}"?`)) {
                api.request(api.route('boardDestroy', { board: store.board().id }), 'DELETE');
            }
        }

        if (action === 'new-task') {
            const firstColumn = store.board().columns[0];
            if (firstColumn) api.request(api.route('tasksStore', { board: store.board().id }), 'POST', { column_id: firstColumn.id, title: 'New task' });
        }

        if (action === 'new-column') {
            const name = window.prompt('Column name', 'New column');
            if (name) api.request(api.route('columnsStore', { board: store.board().id }), 'POST', { name });
        }

        if (action === 'delete-column') {
            const columnId = event.target.closest('.task-column').dataset.columnId;
            if (window.confirm('Delete this column and its tasks?')) {
                api.request(api.route('columnDestroy', { column: columnId }), 'DELETE');
            }
        }

        if (action === 'toggle-task') {
            const clickedTask = taskCard ? store.allTasks().find((item) => item.id === Number(taskCard.dataset.taskId)) : task;
            if (clickedTask) api.persistTask(clickedTask, { completed: ! clickedTask.completed });
        }

        if (action === 'close-detail') {
            store.setSelectedTaskId(null);
            render();
        }

        if (action === 'clear-filters') {
            store.setFilters({ search: '', label: '', priority: '', deadline: '', showArchived: false });
            render();
        }

        if (action === 'archive-task' && task) api.request(api.route('taskArchive', { task: task.id }), 'POST');

        if (action === 'delete-task' && task && window.confirm('Permanently delete task?')) {
            api.request(api.route('taskDestroy', { task: task.id }), 'DELETE').then(() => {
                store.setSelectedTaskId(null);
            });
        }

        if (event.target.matches('[data-priority]') && task) {
            api.persistTask(task, { priority: event.target.dataset.priority });
        }

        if (event.target.matches('[data-label-id]') && task) {
            const labelId = Number(event.target.dataset.labelId);
            const hasLabel = task.labels.some((label) => label.id === labelId);
            const labels = hasLabel ? task.labels.filter((label) => label.id !== labelId) : [...task.labels, store.board().labels.find((label) => label.id === labelId)];
            api.persistTask(task, { labels });
        }
    });

    root.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.target;
        const action = form.dataset.action;
        const task = store.selectedTask();

        if (action === 'quick-add') {
            const title = form.elements.title.value.trim();
            if (! title) return;
            api.request(api.route('tasksStore', { board: store.board().id }), 'POST', {
                column_id: form.closest('.task-column').dataset.columnId,
                title,
            });
        }

        if (action === 'new-label' && task) {
            const name = form.elements.name.value.trim();
            if (! name) return;
            api.persistTask(task, { labels: [...task.labels, { name, color: form.elements.color.value }] });
        }

        if (action === 'new-checklist' && task) {
            const text = form.elements.text.value.trim();
            if (! text) return;
            api.persistTask(task, { checklist: [...task.checklist, { text, completed: false }] });
        }
    });

    root.addEventListener('change', (event) => {
        const task = store.selectedTask();

        if (event.target.matches('[data-filter]')) {
            const name = event.target.dataset.filter;
            store.filters[name] = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
            render();
        }

        if (event.target.matches('[data-field]') && task) {
            api.persistTask(task, { [event.target.dataset.field]: event.target.value || null });
        }
    });

    root.addEventListener('input', (event) => {
        if (event.target.matches('[data-filter="search"]')) {
            store.filters.search = event.target.value;
            render();
            return;
        }

        const task = store.selectedTask();
        if (! task) return;

        if (event.target.matches('[data-field="title"], [data-field="description"]')) {
            clearTimeout(event.target.saveTimer);
            const field = event.target.dataset.field;
            event.target.saveTimer = setTimeout(() => api.persistTask(task, { [field]: event.target.value }), 350);
        }

        if (event.target.matches('[data-action="edit-checklist"]')) {
            const index = Number(event.target.closest('[data-checklist-index]').dataset.checklistIndex);
            const checklist = task.checklist.map((item, current) => current === index ? { ...item, text: event.target.value } : item);
            clearTimeout(event.target.saveTimer);
            event.target.saveTimer = setTimeout(() => api.persistTask(task, { checklist }), 350);
        }
    });

    root.addEventListener('focusout', (event) => {
        if (event.target.matches('[data-action="rename-board"]')) {
            const name = event.target.value.trim();
            if (name && name !== store.board().name) api.request(api.route('boardUpdate', { board: store.board().id }), 'PUT', { name });
        }

        if (event.target.matches('[data-action="rename-column"]')) {
            const columnId = event.target.closest('.task-column').dataset.columnId;
            const column = store.board().columns.find((item) => String(item.id) === String(columnId));
            const name = event.target.value.trim();
            if (name && column && name !== column.name) api.request(api.route('columnUpdate', { column: columnId }), 'PUT', { name });
        }
    });

    root.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        if (event.target.matches('[data-action="rename-board"], [data-action="rename-column"]')) {
            event.target.blur();
        }
    });

    root.addEventListener('click', (event) => {
        const boardButton = event.target.closest('.board-switch');
        if (! boardButton) return;

        const boardId = Number(boardButton.dataset.boardId);
        if (boardId === store.state.activeBoardId) return;

        const nextBoard = store.state.boards.find((item) => item.id === boardId);
        if (! nextBoard) return;

        window.location.href = `/tasks?board=${boardId}`;
    });

    root.addEventListener('click', (event) => {
        const action = event.target.dataset.action;
        const task = store.selectedTask();
        if (! task) return;

        if (action === 'toggle-checklist') {
            const index = Number(event.target.closest('[data-checklist-index]').dataset.checklistIndex);
            const checklist = task.checklist.map((item, current) => current === index ? { ...item, completed: ! item.completed } : item);
            api.persistTask(task, { checklist });
        }

        if (action === 'delete-checklist') {
            const index = Number(event.target.closest('[data-checklist-index]').dataset.checklistIndex);
            api.persistTask(task, { checklist: task.checklist.filter((_, current) => current !== index) });
        }
    });
}
