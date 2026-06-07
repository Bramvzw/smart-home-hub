import Sortable from 'sortablejs';

export function bindSortables(root, store, api) {
    const columnHost = root.querySelector('[data-sortable-columns]');
    if (columnHost) {
        Sortable.create(columnHost, {
            animation: 160,
            draggable: '.task-column',
            handle: '.column-grip',
            ghostClass: 'drag-ghost',
            onEnd: () => {
                const columnIds = [...root.querySelectorAll('.task-column')].map((column) => column.dataset.columnId);
                api.request(api.route('columnsReorder', { board: store.board().id }), 'POST', { column_ids: columnIds });
            },
        });
    }

    root.querySelectorAll('[data-sortable-tasks]').forEach((list) => {
        Sortable.create(list, {
            group: 'tasks',
            animation: 150,
            draggable: '.task-card',
            ghostClass: 'drag-ghost',
            onEnd: (event) => {
                const taskId = event.item.dataset.taskId;
                const columnId = event.to.dataset.columnId;
                const taskIds = [...event.to.querySelectorAll('.task-card')].map((card) => card.dataset.taskId);
                api.request(api.route('taskMove', { task: taskId }), 'PUT', { column_id: columnId, task_ids: taskIds });
            },
        });
    });
}
