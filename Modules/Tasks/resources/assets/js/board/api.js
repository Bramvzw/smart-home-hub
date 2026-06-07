export function createTasksApi({ csrf, routes, store, render }) {
    const route = (name, params = {}) => Object.entries(params).reduce(
        (url, [key, value]) => url.replace(`__${key.toUpperCase()}__`, value),
        routes[name],
    );

    const request = async (url, method = 'GET', body = null) => {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: body ? JSON.stringify(body) : null,
        });

        if (! response.ok) {
            const payload = await response.json().catch(() => ({}));
            throw new Error(payload.message || 'De wijziging kon niet worden opgeslagen.');
        }

        const payload = await response.json();
        if (payload.state) {
            store.setState(payload.state);
            if (payload.selected_task_id) {
                store.setSelectedTaskId(payload.selected_task_id);
            }
            render();
        }

        return payload;
    };

    const persistTask = (task, patch = {}) => request(route('taskUpdate', { task: task.id }), 'PUT', {
        title: task.title,
        description: task.description,
        priority: task.priority,
        due_date: task.due_date,
        completed: task.completed,
        labels: task.labels,
        checklist: task.checklist,
        ...patch,
    });

    return { request, route, persistTask, routes };
}
