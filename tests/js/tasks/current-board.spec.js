import { renderTasksApp } from '../../../Modules/Tasks/resources/assets/js/board/renderers.js';
import { createTasksStore } from '../../../Modules/Tasks/resources/assets/js/board/store.js';
import { deadlineStatus, escapeHtml } from '../../../Modules/Tasks/resources/assets/js/board/formatters.js';

const boardState = {
    boards: [{ id: 1, name: 'Tasks', count: 1 }],
    activeBoardId: 1,
    board: {
        id: 1,
        name: 'Tasks',
        labels: [{ id: 1, name: 'home', color: 'teal' }],
        columns: [
            {
                id: 1,
                name: 'Todo',
                tasks: [{
                    id: 10,
                    title: 'Fix architecture',
                    description: 'Split modules',
                    priority: 'high',
                    due_date: '2026-06-07',
                    completed: false,
                    archived: false,
                    created_at: '2026-06-07',
                    labels: [{ id: 1, name: 'home', color: 'teal' }],
                    checklist: [{ id: 1, text: 'Add tests', completed: true }],
                }],
            },
            { id: 2, name: 'Done', tasks: [] },
        ],
    },
};

describe('Tasks board modules', () => {
    it('renders the current kanban shell from board state', () => {
        const store = createTasksStore(boardState);

        const html = renderTasksApp(store);

        expect(html).toContain('Fix architecture');
        expect(html).toContain('Tasks');
        expect(html).toContain('home');
        expect(html).toContain('1/1');
    });

    it('filters tasks by label and search', () => {
        const store = createTasksStore(boardState);
        const task = store.allTasks()[0];

        expect(store.matchesFilters(task)).toBe(true);

        store.filters.search = 'missing';
        expect(store.matchesFilters(task)).toBe(false);

        store.filters.search = 'architecture';
        store.filters.label = '1';
        expect(store.matchesFilters(task)).toBe(true);
    });

    it('keeps formatting helpers deterministic', () => {
        expect(escapeHtml('<b>Task</b>')).toBe('&lt;b&gt;Task&lt;/b&gt;');
        expect(['overdue', 'today', 'week', '']).toContain(deadlineStatus('2026-06-07'));
    });
});
