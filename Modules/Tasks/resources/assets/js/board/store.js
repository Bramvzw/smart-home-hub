import { deadlineStatus } from './formatters.js';

export function createTasksStore(initialState) {
    let state = initialState;
    let selectedTaskId = null;
    let filters = {
        search: '',
        label: '',
        priority: '',
        deadline: '',
        showArchived: false,
    };

    const board = () => state.board;
    const allTasks = () => board().columns.flatMap((column) => column.tasks.map((task) => ({ ...task, column_id: column.id })));
    const selectedTask = () => allTasks().find((task) => task.id === selectedTaskId) ?? null;

    const matchesFilters = (task) => {
        if (! filters.showArchived && task.archived) return false;
        const query = filters.search.trim().toLowerCase();
        if (query && ! `${task.title} ${task.description}`.toLowerCase().includes(query)) return false;
        if (filters.label && ! task.labels.some((label) => String(label.id) === filters.label)) return false;
        if (filters.priority && task.priority !== filters.priority) return false;
        if (filters.deadline) {
            const status = deadlineStatus(task.due_date);
            if (filters.deadline === 'week') return status === 'today' || status === 'week';
            return status === filters.deadline;
        }
        return true;
    };

    return {
        get state() {
            return state;
        },
        setState(nextState) {
            state = nextState;
        },
        get selectedTaskId() {
            return selectedTaskId;
        },
        setSelectedTaskId(nextTaskId) {
            selectedTaskId = nextTaskId;
        },
        get filters() {
            return filters;
        },
        setFilters(nextFilters) {
            filters = nextFilters;
        },
        board,
        allTasks,
        selectedTask,
        matchesFilters,
    };
}
