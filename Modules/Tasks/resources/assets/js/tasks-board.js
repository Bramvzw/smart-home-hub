// tasks-board.js
import Sortable from 'sortablejs';

// TinyMCE core + model
import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom/model';

// Theme, icons, plugins
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/autoresize';

// Skin & content CSS
import 'tinymce/skins/ui/oxide-dark/skin.min.css';

document.addEventListener('DOMContentLoaded', () => {
    //
    // ─── UTILITIES ───────────────────────────────────────────────────────────────
    //

    const csrfHeader = () => ({
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    });

    const toggleModal = (modal, show) => {
        if (modal) modal.classList.toggle('hidden', !show);
    };

    const initTiny = selector => {
        if (!document.querySelector(selector)) {
            console.warn(`TinyMCE initialization failed: Element ${selector} not found`);
            return;
        }
        const id = selector.replace('#', '');
        if (!tinymce.get(id)) {
            console.log(`Initializing TinyMCE for ${selector}`);
            tinymce.init({
                selector,
                height: 200,
                menubar: false,
                skin: 'dark',
                content_css: 'default',
                content_style: `
                    body {
                      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                      font-size: 0.875rem; /* matches Tailwind's base text-sm (14px) */
                      color: #E5E7EB;      /* Tailwind's text-gray-200, if you want that inside the editor */
                    }
                  `,
                plugins: ['lists','link','image','table','code','codesample','autoresize'],
                toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link image | table | code',
                toolbar_mode: 'floating',
                branding: false,

                // Disable model loading to prevent 404 errors
                models: [],

                // Improve performance and reliability
                entity_encoding: 'raw',
                convert_urls: false,
                relative_urls: false,
                remove_script_host: false,

                // Ensure proper initialization
                init_instance_callback: function(editor) {
                    console.log(`TinyMCE instance initialized for ${selector}`);

                    // Make the textarea visible in case TinyMCE fails
                    const textarea = document.querySelector(selector);
                    if (textarea) {
                        textarea.style.visibility = 'visible';
                    }
                },

                setup: function(editor) {
                    editor.on('init', function() {
                        console.log(`TinyMCE initialized for ${selector}`);
                    });

                    // Add additional event listeners for debugging
                    editor.on('change', function() {
                        console.log(`Content changed in ${selector}`);
                    });

                    editor.on('blur', function() {
                        console.log(`Editor ${selector} lost focus, content:`, editor.getContent());
                    });
                }
            }).then(() => {
                console.log(`TinyMCE initialization completed for ${selector}`);
            }).catch(err => {
                console.error(`TinyMCE initialization error for ${selector}:`, err);
            });
        } else {
            console.log(`TinyMCE already initialized for ${selector}`);
        }
    };

    const removeTiny = selector => {
        try {
            const id = selector.replace('#','');
            const ed = tinymce.get(id);
            if (ed) {
                console.log(`Removing TinyMCE for ${selector}`);
                ed.remove();
                console.log(`TinyMCE removed for ${selector}`);
            } else {
                console.warn(`TinyMCE removal failed: Editor instance for ${selector} not found`);
            }
        } catch (err) {
            console.error(`Error removing TinyMCE for ${selector}:`, err);
        }
    };

    //
    // ─── SORTABLE SETUP ───────────────────────────────────────────────────────────
    //

    document.querySelectorAll('.tasks-container').forEach(container => {
        new Sortable(container, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'bg-gray-600',
            onEnd(evt) {
                const id   = evt.item.dataset.taskId;
                const lane = evt.to.closest('.lane').dataset.laneId;
                const idx  = Array.from(evt.to.children).indexOf(evt.item);

                fetch(`/tasks/tasks/${id}/move`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        ...csrfHeader()
                    },
                    body: JSON.stringify({ lane_id: lane, order: idx })
                })
                    .then(r => r.json())
                    .then(json => { if (!json.success) console.error(json.message); })
                    .catch(console.error);
            }
        });
    });

    //
    // ─── LANE CRUD ────────────────────────────────────────────────────────────────
    //

    // Add Lane
    const addLaneBtn   = document.getElementById('add-lane-button');
    const addLaneModal = document.getElementById('add-lane-modal');
    const addLaneForm  = document.getElementById('add-lane-form');

    addLaneBtn?.addEventListener('click',    () => toggleModal(addLaneModal, true));
    document.getElementById('cancel-add-lane')
        ?.addEventListener('click', () => { toggleModal(addLaneModal, false); addLaneForm?.reset(); });
    addLaneForm?.addEventListener('submit', e => {
        e.preventDefault();
        fetch('/tasks/lanes', {
            method: 'POST',
            headers: csrfHeader(),
            body: new FormData(addLaneForm)
        })
            .then(r => r.json())
            .then(json => json.success ? window.location.reload() : console.error(json.message))
            .catch(console.error);
    });

    // Edit Lane
    const editLaneBtns  = document.querySelectorAll('.edit-lane-button');
    const editLaneModal = document.getElementById('edit-lane-modal');
    const editLaneForm  = document.getElementById('edit-lane-form');

    editLaneBtns.forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('edit-lane-id').value   = btn.dataset.laneId;
        document.getElementById('edit-lane-name').value = btn.dataset.laneName;
        toggleModal(editLaneModal, true);
    }));
    ['cancel-edit-lane','cancel-edit-lane-btn'].forEach(id =>
        document.getElementById(id)
            ?.addEventListener('click', () => { toggleModal(editLaneModal, false); editLaneForm?.reset(); })
    );
    editLaneForm?.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('edit-lane-id').value;
        fetch(`/tasks/lanes/${id}`, {
            method: 'PUT',
            headers: csrfHeader(),
            body: new FormData(editLaneForm)
        })
            .then(r => r.json())
            .then(json => json.success ? window.location.reload() : console.error(json.message))
            .catch(console.error);
    });

    // Delete Lane
    document.querySelectorAll('.delete-lane-button').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Delete this lane and all its tasks?')) return;
            fetch(`/tasks/lanes/${btn.dataset.laneId}`, {
                method: 'DELETE',
                headers: csrfHeader()
            })
                .then(r => r.json())
                .then(json => json.success ? window.location.reload() : console.error(json.message))
                .catch(console.error);
        });
    });

    //
    // ─── TASK CRUD ────────────────────────────────────────────────────────────────
    //

    // ADD TASK
    const addTaskBtns   = document.querySelectorAll('.add-task-button');
    const addTaskModal  = document.getElementById('add-task-modal');
    const addTaskForm   = document.getElementById('add-task-form');
    const addTaskLaneId = document.getElementById('task-lane-id');

    addTaskBtns.forEach(btn => btn.addEventListener('click', () => {
        addTaskLaneId.value = btn.dataset.laneId;
        toggleModal(addTaskModal, true);
        initTiny('#task-description');
    }));
    ['cancel-add-task','cancel-add-task-btn'].forEach(id =>
        document.getElementById(id)
            ?.addEventListener('click', () => {
                toggleModal(addTaskModal, false);
                addTaskForm?.reset();
                removeTiny('#task-description');
            })
    );
    addTaskForm?.addEventListener('submit', e => {
        e.preventDefault();

        // Get TinyMCE content with retry mechanism
        let description = '';

        // Function to get TinyMCE content and submit the form
        const getTinyMCEContentAndSubmit = (attempts = 0) => {
            const editor = tinymce.get('task-description');
            if (editor) {
                description = editor.getContent();
                submitForm();
                return true;
            } else if (attempts < 3) {
                // Retry up to 3 times with increasing delay
                setTimeout(() => {
                    if (getTinyMCEContentAndSubmit(attempts + 1)) {
                        console.log('Successfully got TinyMCE content on retry');
                    }
                }, 100 * (attempts + 1));
            } else {
                console.warn('Failed to get TinyMCE content after multiple attempts');
                // Submit the form anyway with empty description
                submitForm();
            }
            return false;
        };

        // Function to submit the form
        const submitForm = () => {
            console.log('Submitting form with description:', description);

            // Log form data for debugging
            const fd = new FormData(addTaskForm);
            fd.set('description', description || '');

            console.log('Form data being submitted:');
            for (let [key, value] of fd.entries()) {
                console.log(`${key}: ${value.length > 100 ? value.substring(0, 100) + '...' : value}`);
            }

            console.log('Sending POST request to /tasks/tasks');
            fetch('/tasks/tasks', {
                method: 'POST',
                headers: csrfHeader(),
                body: fd
            })
                .then(response => {
                    console.log(`Response received: status ${response.status} ${response.statusText}`);

                    // Log response headers for debugging
                    console.log('Response headers:');
                    response.headers.forEach((value, name) => {
                        console.log(`${name}: ${value}`);
                    });

                    // 1️⃣ Redirect (e.g. unauthenticated or normal redirect) → just follow it
                    if (response.redirected) {
                        console.log(`Redirected to: ${response.url}`);
                        window.location.href = response.url;
                        return Promise.reject('redirect');
                    }

                    // 2️⃣ JSON → parse & return
                    const ct = response.headers.get('content-type') || '';
                    console.log(`Content-Type: ${ct}`);

                    if (ct.includes('application/json')) {
                        console.log('Parsing JSON response');
                        return response.json().then(data => {
                            console.log('Parsed JSON response:', data);
                            return data;
                        });
                    }

                    // 3️⃣ OK HTML → assume success, reload
                    if (response.ok) {
                        console.log('Response OK but not JSON, reloading page');
                        window.location.reload();
                        return Promise.reject('reload');
                    }

                    // 4️⃣ Anything else → error
                    console.error(`Error response: ${response.status} ${response.statusText}`);
                    throw new Error(`Expected JSON but got ${response.status} ${ct}`);
                })
                .then(json => {
                    if (json.success) {
                        window.location.reload();
                    } else {
                        console.error('Server error:', json.message);
                    }
                })
                .catch(err => {
                    if (err !== 'redirect' && err !== 'reload') {
                        console.error('Error submitting task:', err);
                    }
                });
        };

        // Start the process
        getTinyMCEContentAndSubmit();
    });

    // EDIT TASK
    const editTaskBtns   = document.querySelectorAll('.edit-task-button');
    const editTaskModal  = document.getElementById('edit-task-modal');
    const editTaskForm   = document.getElementById('edit-task-form');

    editTaskBtns.forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('edit-task-id').value = btn.dataset.taskId;
        document.getElementById('edit-task-title').value = btn.dataset.taskTitle;
        document.getElementById('edit-task-label').value = btn.dataset.taskLabel || '';
        document.getElementById('edit-task-priority').value = btn.dataset.taskPriority || '';
        document.getElementById('edit-task-due-date').value = btn.dataset.taskDueDate || '';
        document.getElementById('edit-task-notify').checked = btn.dataset.taskNotify === 'true';
        document.getElementById('edit-task-id').value    = btn.dataset.taskId;
        document.getElementById('edit-task-title').value = btn.dataset.taskTitle;
        // …

        // 1) Grab the entity‐escaped string
        let raw = btn.getAttribute('data-task-description') || '';

        // 2) Decode HTML entities
        //    using a <textarea> or DOMParser:
        const txt = document.createElement('textarea');
        txt.innerHTML = raw;
        raw = txt.value;  // now "<p>test</p>"

        const ta = document.getElementById('edit-task-description');
        ta.value = raw;
        toggleModal(editTaskModal, true);
        initTiny('#edit-task-description');

    }));
    ['cancel-edit-task','cancel-edit-task-btn'].forEach(id =>
        document.getElementById(id)
            ?.addEventListener('click', () => {
                toggleModal(editTaskModal, false);
                editTaskForm?.reset();
                removeTiny('#edit-task-description');
            })
    );
    editTaskForm?.addEventListener('submit', e => {
        e.preventDefault();

        // Get TinyMCE content with retry mechanism
        let description = '';

        // Function to get TinyMCE content and submit the form
        const getTinyMCEContentAndSubmit = (attempts = 0) => {
            const editor = tinymce.get('edit-task-description');
            if (editor) {
                description = editor.getContent();
                submitForm();
                return true;
            } else if (attempts < 3) {
                // Retry up to 3 times with increasing delay
                setTimeout(() => {
                    if (getTinyMCEContentAndSubmit(attempts + 1)) {
                        console.log('Successfully got TinyMCE content on retry for edit task');
                    }
                }, 100 * (attempts + 1));
            } else {
                console.warn('Failed to get TinyMCE content after multiple attempts for edit task');
                // Submit the form anyway with empty description
                submitForm();
            }
            return false;
        };

        // Function to submit the form
        const submitForm = () => {
            console.log('Submitting edit form with description:', description);
            const id = document.getElementById('edit-task-id').value;

            // Log form data for debugging
            const fd = new FormData(editTaskForm);
            fd.set('description', description || '');

            console.log('Form data being submitted for edit:');
            for (let [key, value] of fd.entries()) {
                console.log(`${key}: ${value.length > 100 ? value.substring(0, 100) + '...' : value}`);
            }

            console.log(`Sending PUT request to /tasks/tasks/${id}`);
            fetch(`/tasks/tasks/${id}`, {
                method: 'PUT',
                headers: csrfHeader(),
                body: fd
            })
                .then(response => {
                    console.log(`Response received: status ${response.status} ${response.statusText}`);

                    // Log response headers for debugging
                    console.log('Response headers:');
                    response.headers.forEach((value, name) => {
                        console.log(`${name}: ${value}`);
                    });

                    // 1️⃣ Redirect (e.g. unauthenticated or normal redirect) → just follow it
                    if (response.redirected) {
                        console.log(`Redirected to: ${response.url}`);
                        window.location.href = response.url;
                        return Promise.reject('redirect');
                    }

                    // 2️⃣ JSON → parse & return
                    const ct = response.headers.get('content-type') || '';
                    console.log(`Content-Type: ${ct}`);

                    if (ct.includes('application/json')) {
                        console.log('Parsing JSON response');
                        return response.json().then(data => {
                            console.log('Parsed JSON response:', data);
                            return data;
                        });
                    }

                    // 3️⃣ OK HTML → assume success, reload
                    if (response.ok) {
                        console.log('Response OK but not JSON, reloading page');
                        window.location.reload();
                        return Promise.reject('reload');
                    }

                    // 4️⃣ Anything else → error
                    console.error(`Error response: ${response.status} ${response.statusText}`);
                    throw new Error(`Expected JSON but got ${response.status} ${ct}`);
                })
                .then(json => {
                    if (json.success) {
                        window.location.reload();
                    } else {
                        console.error('Server error:', json.message);
                    }
                })
                .catch(err => {
                    if (err !== 'redirect' && err !== 'reload') {
                        console.error('Error updating task:', err);
                    }
                });
        };

        // Start the process
        getTinyMCEContentAndSubmit();
    });

    // DELETE TASK
    document.querySelectorAll('.delete-task-button').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Delete this task?')) return;
            fetch(`/tasks/tasks/${btn.dataset.taskId}`, {
                method: 'DELETE',
                headers: csrfHeader()
            })
                .then(r => r.json())
                .then(json => json.success ? window.location.reload() : console.error(json.message))
                .catch(console.error);
        });
    });
});
