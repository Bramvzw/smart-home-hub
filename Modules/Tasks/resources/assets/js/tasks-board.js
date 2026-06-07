import { createTasksApi } from './board/api.js';
import { bindTaskEvents } from './board/events.js';
import { renderTasksApp } from './board/renderers.js';
import { bindSortables } from './board/sortables.js';
import { createTasksStore } from './board/store.js';

const root = document.getElementById('tasks-app');

if (root) {
    const store = createTasksStore(JSON.parse(root.dataset.state));
    let api;

    const render = () => {
        root.innerHTML = renderTasksApp(store);
        bindSortables(root, store, api);
    };

    api = createTasksApi({
        csrf: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        routes: JSON.parse(root.dataset.routes),
        store,
        render,
    });

    bindTaskEvents(root, store, api, render);
    render();
}
