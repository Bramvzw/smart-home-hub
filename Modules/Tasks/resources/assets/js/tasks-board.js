import Sortable from 'sortablejs';

// Import TinyMCE
import tinymce from 'tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/autoresize';

document.addEventListener('DOMContentLoaded', function() {
    // Load label options
    const labelDatalist = document.getElementById('label-options');
    if (labelDatalist) {
        fetch('/tasks/labels')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    labelDatalist.innerHTML = '';
                    data.labels.forEach(label => {
                        const opt = document.createElement('option');
                        opt.value = label;
                        labelDatalist.appendChild(opt);
                    });
                }
            })
            .catch(() => {});
    }
    // Initialize sortable for each lane's tasks container
    const taskContainers = document.querySelectorAll('.tasks-container');
    taskContainers.forEach(container => {
        new Sortable(container, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'bg-gray-600 opacity-70',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newLaneId = evt.to.closest('.lane').dataset.laneId;
                const newIndex = Array.from(evt.to.children).indexOf(evt.item);

                // Call API to update task position
                fetch(`/tasks/tasks/${taskId}/move`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        lane_id: newLaneId,
                        order: newIndex
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to move task:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error moving task:', error);
                });
            }
        });
    });

    // Add Lane Button
    const addLaneButton = document.getElementById('add-lane-button');
    const addLaneModal = document.getElementById('add-lane-modal');
    const addLaneForm = document.getElementById('add-lane-form');
    const cancelAddLane = document.getElementById('cancel-add-lane');

    addLaneButton.addEventListener('click', function() {
        addLaneModal.classList.remove('hidden');
    });

    cancelAddLane.addEventListener('click', function() {
        addLaneModal.classList.add('hidden');
        addLaneForm.reset();
    });

    addLaneForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(addLaneForm);

        fetch('/tasks/lanes', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the new lane
                window.location.reload();
            } else {
                console.error('Failed to create lane:', data.message);
            }
        })
        .catch(error => {
            console.error('Error creating lane:', error);
        });
    });

    // Edit Lane Buttons
    const editLaneButtons = document.querySelectorAll('.edit-lane-button');
    const editLaneModal = document.getElementById('edit-lane-modal');
    const editLaneForm = document.getElementById('edit-lane-form');
    const editLaneId = document.getElementById('edit-lane-id');
    const editLaneName = document.getElementById('edit-lane-name');
    const cancelEditLane = document.getElementById('cancel-edit-lane');
    const cancelEditLaneBtn = document.getElementById('cancel-edit-lane-btn');

    editLaneButtons.forEach(button => {
        button.addEventListener('click', function() {
            const laneId = this.dataset.laneId;
            const laneName = this.dataset.laneName;

            editLaneId.value = laneId;
            editLaneName.value = laneName;

            editLaneModal.classList.remove('hidden');
        });
    });

    // Handle both cancel buttons
    const closeEditLaneModal = function() {
        editLaneModal.classList.add('hidden');
        editLaneForm.reset();
    };

    cancelEditLane.addEventListener('click', closeEditLaneModal);
    cancelEditLaneBtn.addEventListener('click', closeEditLaneModal);

    editLaneForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const laneId = editLaneId.value;
        const formData = new FormData(editLaneForm);

        fetch(`/tasks/lanes/${laneId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the updated lane
                window.location.reload();
            } else {
                console.error('Failed to update lane:', data.message);
            }
        })
        .catch(error => {
            console.error('Error updating lane:', error);
        });
    });

    // Delete Lane Buttons
    const deleteLaneButtons = document.querySelectorAll('.delete-lane-button');

    deleteLaneButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this lane and all its tasks?')) {
                const laneId = this.dataset.laneId;

                fetch(`/tasks/lanes/${laneId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show the updated lanes
                        window.location.reload();
                    } else {
                        console.error('Failed to delete lane:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting lane:', error);
                });
            }
        });
    });

    // Add Task Buttons
    const addTaskButtons = document.querySelectorAll('.add-task-button');
    const addTaskModal = document.getElementById('add-task-modal');
    const addTaskForm = document.getElementById('add-task-form');
    const taskLaneId = document.getElementById('task-lane-id');
    const cancelAddTask = document.getElementById('cancel-add-task');
    const cancelAddTaskBtn = document.getElementById('cancel-add-task-btn');

    addTaskButtons.forEach(button => {
        button.addEventListener('click', function() {
            const laneId = this.dataset.laneId;

            taskLaneId.value = laneId;

            addTaskModal.classList.remove('hidden');
        });
    });

    // Handle both cancel buttons
    const closeAddTaskModal = function() {
        addTaskModal.classList.add('hidden');
        addTaskForm.reset();
    };

    cancelAddTask.addEventListener('click', closeAddTaskModal);
    cancelAddTaskBtn.addEventListener('click', closeAddTaskModal);

    addTaskForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Get TinyMCE content
        const description = tinymce.get('task-description').getContent();

        // Create a new FormData object
        const formData = new FormData();

        // Add the essential fields
        formData.append('title', document.getElementById('task-title').value);
        formData.append('description', description);
        formData.append('lane_id', document.getElementById('task-lane-id').value);

        // Add optional fields if they have values
        const label = document.getElementById('task-label').value;
        if (label) {
            formData.append('label', label);
        }

        const priority = document.getElementById('task-priority').value;
        if (priority) {
            formData.append('priority', priority);
        }

        const dueDate = document.getElementById('task-due-date').value;
        if (dueDate) {
            formData.append('due_date', dueDate);
        }

        const notifyBeforeExpiry = document.getElementById('task-notify').checked;
        formData.append('notify_before_expiry', notifyBeforeExpiry ? '1' : '0');

        // Add URLs
        const urlInputs = document.querySelectorAll('#task-urls-container input[name="urls[]"]');
        const urls = Array.from(urlInputs).map(input => input.value).filter(url => url.trim() !== '');
        urls.forEach(url => {
            formData.append('urls[]', url);
        });

        fetch('/tasks/tasks', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the new task
                window.location.reload();
            } else {
                console.error('Failed to create task:', data.message);
            }
        })
        .catch(error => {
            console.error('Error creating task:', error);
        });
    });

    // Edit Task Buttons
    const editTaskButtons = document.querySelectorAll('.edit-task-button');
    const editTaskModal = document.getElementById('edit-task-modal');
    const editTaskForm = document.getElementById('edit-task-form');
    const editTaskId = document.getElementById('edit-task-id');
    const editTaskTitle = document.getElementById('edit-task-title');
    const editTaskDescription = document.getElementById('edit-task-description');
    const editTaskLabel = document.getElementById('edit-task-label');
    const editTaskPriority = document.getElementById('edit-task-priority');
    const editTaskDueDate = document.getElementById('edit-task-due-date');
    const editTaskNotify = document.getElementById('edit-task-notify');
    const cancelEditTask = document.getElementById('cancel-edit-task');
    const cancelEditTaskBtn = document.getElementById('cancel-edit-task-btn');

    editTaskButtons.forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            const taskTitle = this.dataset.taskTitle;
            const taskDescription = this.dataset.taskDescription;
            const taskLabel = this.dataset.taskLabel;
            const taskDueDate = this.dataset.taskDueDate;
            const taskPriority = this.dataset.taskPriority;
            const taskNotify = this.dataset.taskNotify === 'true';
            const taskUrls = JSON.parse(this.dataset.taskUrls || '[]');

            editTaskId.value = taskId;
            editTaskTitle.value = taskTitle;

            // Set TinyMCE content
            setTimeout(() => {
                tinymce.get('edit-task-description').setContent(taskDescription || '');
            }, 100);

            editTaskLabel.value = taskLabel || '';
            editTaskPriority.value = taskPriority || '';
            editTaskDueDate.value = taskDueDate || '';
            editTaskNotify.checked = taskNotify;

            // Populate URLs
            populateUrls(taskUrls);

            editTaskModal.classList.remove('hidden');
        });
    });

    // Handle both cancel buttons
    const closeEditTaskModal = function() {
        editTaskModal.classList.add('hidden');
        editTaskForm.reset();
    };

    cancelEditTask.addEventListener('click', closeEditTaskModal);
    cancelEditTaskBtn.addEventListener('click', closeEditTaskModal);

    editTaskForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Get TinyMCE content
        const description = tinymce.get('edit-task-description').getContent();

        const taskId = editTaskId.value;

        // Create a new FormData object
        const formData = new FormData();

        // Add the essential fields
        formData.append('title', document.getElementById('edit-task-title').value);
        formData.append('description', description);

        // Add optional fields if they have values
        const label = document.getElementById('edit-task-label').value;
        if (label) {
            formData.append('label', label);
        }

        const priority = document.getElementById('edit-task-priority').value;
        if (priority) {
            formData.append('priority', priority);
        }

        const dueDate = document.getElementById('edit-task-due-date').value;
        if (dueDate) {
            formData.append('due_date', dueDate);
        }

        const notifyBeforeExpiry = document.getElementById('edit-task-notify').checked;
        formData.append('notify_before_expiry', notifyBeforeExpiry ? '1' : '0');

        // Add URLs
        const urlInputs = document.querySelectorAll('#edit-task-urls-container input[name="urls[]"]');
        const urls = Array.from(urlInputs).map(input => input.value).filter(url => url.trim() !== '');
        urls.forEach(url => {
            formData.append('urls[]', url);
        });

        fetch(`/tasks/tasks/${taskId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show the updated task
                window.location.reload();
            } else {
                console.error('Failed to update task:', data.message);
            }
        })
        .catch(error => {
            console.error('Error updating task:', error);
        });
    });

    // Delete Task Buttons
    const deleteTaskButtons = document.querySelectorAll('.delete-task-button');

    deleteTaskButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this task?')) {
                const taskId = this.dataset.taskId;

                fetch(`/tasks/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show the updated tasks
                        window.location.reload();
                    } else {
                        console.error('Failed to delete task:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting task:', error);
                });
            }
        });
    });

    // Initialize TinyMCE for task description fields
    function initTinyMCE() {
        tinymce.init({
            selector: '#task-description, #edit-task-description',
            height: 200,
            menubar: false,
            plugins: [
                'lists', 'link', 'image', 'table', 'code', 'codesample', 'autoresize'
            ],
            toolbar: 'undo redo | formatselect | bold italic | bullist numlist checklist | link image | table | code',
            toolbar_mode: 'floating',
            skin: 'oxide-dark',
            content_css: 'dark',
            branding: false,
            promotion: false,
            setup: function(editor) {
                // Add keyboard shortcuts
                editor.addShortcut('meta+b', 'Bold', 'Bold');
                editor.addShortcut('meta+i', 'Italic', 'Italic');
                editor.addShortcut('meta+u', 'Underline', 'Underline');
                editor.addShortcut('meta+k', 'Link', 'Link');
                editor.addShortcut('meta+shift+7', 'Numbered list', 'InsertOrderedList');
                editor.addShortcut('meta+shift+8', 'Bullet list', 'InsertUnorderedList');
                editor.addShortcut('meta+shift+9', 'Checklist', function() {
                    editor.execCommand('InsertUnorderedList', false, { 'list-style-type': 'checklist' });
                });
            }
        });
    }

    // Initialize TinyMCE when the page loads
    initTinyMCE();

    // URL Input Handling
    const addUrlButtonNew = document.getElementById('add-url-button-new');
    const taskUrlsContainer = document.getElementById('task-urls-container');
    const addUrlButton = document.getElementById('add-url-button');
    const editTaskUrlsContainer = document.getElementById('edit-task-urls-container');

    // Function to create a new URL input
    function createUrlInput(container, value = '') {
        const urlInputWrapper = document.createElement('div');
        urlInputWrapper.className = 'flex space-x-2';

        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.name = 'urls[]';
        urlInput.className = 'flex-grow px-4 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors';
        urlInput.placeholder = 'https://example.com';
        urlInput.value = value;

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'remove-url-button bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-red-400 font-medium py-2 px-3 rounded-lg transition-colors text-sm';
        removeButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';

        removeButton.addEventListener('click', function() {
            urlInputWrapper.remove();
        });

        urlInputWrapper.appendChild(urlInput);
        urlInputWrapper.appendChild(removeButton);
        container.appendChild(urlInputWrapper);
    }

    // Add URL button for new task
    if (addUrlButtonNew) {
        addUrlButtonNew.addEventListener('click', function() {
            createUrlInput(taskUrlsContainer);
        });
    }

    // Add URL button for edit task
    if (addUrlButton) {
        addUrlButton.addEventListener('click', function() {
            createUrlInput(editTaskUrlsContainer);
        });
    }

    // Add event listeners to existing remove URL buttons
    document.querySelectorAll('.remove-url-button').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.flex').remove();
        });
    });

    // Function to populate URLs in edit task modal
    function populateUrls(urls) {
        // Clear existing URLs
        editTaskUrlsContainer.innerHTML = '';

        // Add each URL
        if (urls && urls.length > 0) {
            urls.forEach(url => {
                createUrlInput(editTaskUrlsContainer, url);
            });
        } else {
            // Add an empty URL input if there are no URLs
            createUrlInput(editTaskUrlsContainer);
        }
    }

    // We don't need to update the edit task button click handler since we're using addEventListener
    // Instead, let's modify the existing click handler to also populate URLs
    const originalEditTaskButtonHandler = editTaskButtons.forEach;
    editTaskButtons.forEach = function(callback) {
        return originalEditTaskButtonHandler.call(this, function(button) {
            // Call the original callback
            callback(button);

            // Add our URL population logic
            button.addEventListener('click', function() {
                const taskUrls = JSON.parse(this.dataset.taskUrls || '[]');
                setTimeout(() => {
                    populateUrls(taskUrls);
                }, 100);
            });
        });
    };

    // Search and Filter functionality
    const searchInput = document.getElementById('task-search');
    const filterButton = document.getElementById('filter-button');
    const clearFiltersButton = document.getElementById('clear-filters-button');
    const filterOptions = document.getElementById('filter-options');
    const filterPriority = document.getElementById('filter-priority');
    const filterLabel = document.getElementById('filter-label');
    const filterDueDate = document.getElementById('filter-due-date');

    // Toggle filter options
    filterButton.addEventListener('click', function() {
        filterOptions.classList.toggle('hidden');
    });

    // Clear filters
    clearFiltersButton.addEventListener('click', function() {
        searchInput.value = '';
        filterPriority.value = '';
        filterLabel.value = '';
        filterDueDate.value = '';

        // Show all tasks
        document.querySelectorAll('.task').forEach(task => {
            task.style.display = 'block';
        });
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        filterTasks();
    });

    // Filter change events
    filterPriority.addEventListener('change', filterTasks);
    filterLabel.addEventListener('input', filterTasks);
    filterDueDate.addEventListener('change', filterTasks);

    // Filter tasks based on search and filter criteria
    function filterTasks() {
        const searchTerm = searchInput.value.toLowerCase();
        const priorityFilter = filterPriority.value.toLowerCase();
        const labelFilter = filterLabel.value.toLowerCase();
        const dueDateFilter = filterDueDate.value;

        // Get all tasks
        const tasks = document.querySelectorAll('.task');

        tasks.forEach(task => {
            const title = task.querySelector('h4').textContent.toLowerCase();
            const description = task.querySelector('p') ? task.querySelector('p').textContent.toLowerCase() : '';
            const label = task.dataset.label ? task.dataset.label.toLowerCase() : '';
            const priority = task.dataset.priority ? task.dataset.priority.toLowerCase() : '';
            const isOverdue = task.dataset.overdue === 'true';
            const isExpiring = task.dataset.expiring === 'true';

            // Check if task matches search term
            const matchesSearch = searchTerm === '' ||
                title.includes(searchTerm) ||
                description.includes(searchTerm);

            // Check if task matches priority filter
            const matchesPriority = priorityFilter === '' || priority === priorityFilter;

            // Check if task matches label filter
            const matchesLabel = labelFilter === '' || label === labelFilter;

            // Check if task matches due date filter
            let matchesDueDate = true;
            if (dueDateFilter !== '') {
                switch (dueDateFilter) {
                    case 'overdue':
                        matchesDueDate = isOverdue;
                        break;
                    case 'today':
                        matchesDueDate = isExpiring && !isOverdue;
                        break;
                    // Add more cases for other due date filters
                    default:
                        matchesDueDate = true;
                }
            }

            // Show or hide task based on filters
            if (matchesSearch && matchesPriority && matchesLabel && matchesDueDate) {
                task.style.display = 'block';
            } else {
                task.style.display = 'none';
            }
        });
    }

    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === addLaneModal) {
            addLaneModal.classList.add('hidden');
            addLaneForm.reset();
            tinymce.get('task-description')?.setContent('');
        } else if (e.target === editLaneModal) {
            editLaneModal.classList.add('hidden');
            editLaneForm.reset();
        } else if (e.target === addTaskModal) {
            addTaskModal.classList.add('hidden');
            addTaskForm.reset();
            tinymce.get('task-description')?.setContent('');
        } else if (e.target === editTaskModal) {
            editTaskModal.classList.add('hidden');
            editTaskForm.reset();
            tinymce.get('edit-task-description')?.setContent('');
        }
    });
});
