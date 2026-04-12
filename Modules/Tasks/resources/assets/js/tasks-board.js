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
        if (!document.querySelector(selector)) return;
        const id = selector.replace('#', '');
        if (!tinymce.get(id)) {
            tinymce.init({
                selector: '#task-description, #edit-task-description',
                height: 200,
                menubar: false,
                skin: false,
                content_css: false,
                content_style:
                    `
                    body { 
                  background-color: #364153 !important; 
                  color: #ffffff !important;
                  padding: 0.5em;
                  margin: 0;
                  font-family: system-ui;
                }
                /* your custom scrollbars */
                ::-webkit-scrollbar { width: 4px; }
                ::-webkit-scrollbar-track {
                  background: rgba(75,85,99,0.2);
                  border-radius: 10px;
                }
                ::-webkit-scrollbar-thumb {
                  background: rgba(74,7,27,0.5);
                  border-radius: 10px;
                }
                ::-webkit-scrollbar-thumb:hover {
                  background: rgba(99,102,241,0.8);
                }
              `,

                plugins: ['lists','link','image','table','code','codesample'],
                toolbar: 'formatselect | bold italic | bullist numlist | link | table | code',
                autoresize_bottom_margin: 10,
                autoresize_overflow_padding: 0,
                resize: false,
                toolbar_mode: 'floating',
                branding: false,

                // Disable model loading to prevent 404 errors
                models: [],

                // Improve performance and reliability
                entity_encoding: 'raw',
                convert_urls: false,
                relative_urls: false,
                remove_script_host: false,

                init_instance_callback: function(editor) {
                    const textarea = document.querySelector(selector);
                    if (textarea) {
                        textarea.style.visibility = 'visible';
                    }
                },

                setup: function(editor) {

                }
            }).catch(err => {
                console.error('TinyMCE initialization error:', err);
            });
        }
    };

    const removeTiny = selector => {
        const id = selector.replace('#','');
        const ed = tinymce.get(id);
        if (ed) ed.remove();
    };

    // Shared helper: get TinyMCE content with retry, then call callback
    const getTinyContentAndSubmit = (editorId, callback, attempts = 0) => {
        const editor = tinymce.get(editorId);
        if (editor) {
            callback(editor.getContent());
        } else if (attempts < 3) {
            setTimeout(() => getTinyContentAndSubmit(editorId, callback, attempts + 1), 100 * (attempts + 1));
        } else {
            callback('');
        }
    };

    // Shared helper: submit form data and handle response
    const submitTaskForm = (url, method, fd) => {
        fetch(url, { method, headers: csrfHeader(), body: fd })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return Promise.reject('redirect');
                }
                const ct = response.headers.get('content-type') || '';
                if (ct.includes('application/json')) return response.json();
                if (response.ok) {
                    window.location.reload();
                    return Promise.reject('reload');
                }
                throw new Error(`Unexpected response: ${response.status}`);
            })
            .then(json => {
                if (json.success) window.location.reload();
                else console.error('Server error:', json.message);
            })
            .catch(err => {
                if (err !== 'redirect' && err !== 'reload') console.error('Error:', err);
            });
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
    document.querySelectorAll('.cancel-add-lane').forEach(btn =>
        btn.addEventListener('click', () => { toggleModal(addLaneModal, false); addLaneForm?.reset(); })
    );
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
        const ta = document.getElementById('task-description');
        ta.style.height = '0px'; // Set initial height to 0 to prevent flash
        initTiny('#task-description');
        toggleModal(addTaskModal, true);
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
        getTinyContentAndSubmit('task-description', description => {
            const fd = new FormData(addTaskForm);
            fd.set('description', description || '');
            submitTaskForm('/tasks/tasks', 'POST', fd);
        });
    });

    // EDIT TASK
    const editTaskBtns   = document.querySelectorAll('.edit-task-button');
    const editTaskModal  = document.getElementById('edit-task-modal');
    const editTaskForm   = document.getElementById('edit-task-form');

    editTaskBtns.forEach(btn => btn.addEventListener('click', () => {
        const taskId = btn.dataset.taskId;
        document.getElementById('edit-task-id').value = taskId;
        document.getElementById('edit-task-title').value = btn.dataset.taskTitle;
        document.getElementById('edit-task-label').value = btn.dataset.taskLabel || '';
        document.getElementById('edit-task-priority').value = btn.dataset.taskPriority || '';
        document.getElementById('edit-task-due-date').value = btn.dataset.taskDueDate || '';
        document.getElementById('edit-task-notify').checked = btn.dataset.taskNotify === 'true';

        // 1) Grab the entity‐escaped string
        let raw = btn.getAttribute('data-task-description') || '';

        // 2) Decode HTML entities
        //    using a <textarea> or DOMParser:
        const txt = document.createElement('textarea');
        txt.innerHTML = raw;
        raw = txt.value;  // now "<p>test</p>"

        const ta = document.getElementById('edit-task-description');
        ta.value = raw;
        ta.style.height = '0px'; // Set initial height to 0 to prevent flash
        initTiny('#edit-task-description');


        toggleModal(editTaskModal, true);

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
        getTinyContentAndSubmit('edit-task-description', description => {
            const id = document.getElementById('edit-task-id').value;
            const fd = new FormData(editTaskForm);
            fd.set('description', description || '');
            submitTaskForm(`/tasks/tasks/${id}`, 'PUT', fd);
        });
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

    //
    // ─── SEARCH AND FILTER ────────────────────────────────────────────────────────
    //

    // Add CSS for animations if not already in stylesheet
    if (!document.getElementById('filter-animations-css')) {
        const style = document.createElement('style');
        style.id = 'filter-animations-css';
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in {
                animation: fadeIn 0.3s ease-out forwards;
            }
        `;
        document.head.appendChild(style);
    }

    // Toggle filter options with animation
    const filterButton = document.getElementById('filter-button');
    const filterOptions = document.getElementById('filter-options');

    filterButton?.addEventListener('click', () => {
        const isHidden = filterOptions.classList.contains('hidden');

        if (isHidden) {
            // Show the filter options
            filterOptions.classList.remove('hidden');
            // Force a reflow to ensure the animation works
            void filterOptions.offsetWidth;
            // Add animation class
            filterOptions.classList.add('animate-fade-in');
        } else {
            // Hide the filter options
            filterOptions.classList.add('hidden');
            filterOptions.classList.remove('animate-fade-in');
        }
    });

    // Search clear button functionality
    const searchInput = document.getElementById('task-search');
    const searchClear = document.getElementById('search-clear');

    if (searchInput && searchClear) {
        // Initialize clear button state based on input content
        if (searchInput.value.length > 0) {
            searchClear.classList.remove('hidden', 'opacity-0');
            searchClear.classList.add('opacity-100');
        }

        // Show/hide clear button based on input content
        searchInput.addEventListener('input', () => {
            if (searchInput.value.length > 0) {
                searchClear.classList.remove('hidden', 'opacity-0');
                searchClear.classList.add('opacity-100');
            } else {
                searchClear.classList.add('opacity-0');
                setTimeout(() => {
                    if (searchInput.value.length === 0) {
                        searchClear.classList.add('hidden');
                    }
                }, 300);
            }
        });

        // Clear search input when clear button is clicked
        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            searchClear.classList.add('opacity-0');
            setTimeout(() => {
                searchClear.classList.add('hidden');
            }, 300);
            filterTasks();
        });

        // Add focus effect to search input
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.classList.add('ring-2', 'ring-indigo-500');
        });

        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.classList.remove('ring-2', 'ring-indigo-500');
        });
    }

    // Run initial filter to set up the UI correctly
    setTimeout(() => {
        filterTasks();
    }, 100);

    // Clear filters
    const clearFiltersButton = document.getElementById('clear-filters-button');

    clearFiltersButton?.addEventListener('click', () => {
        // Clear all inputs
        document.getElementById('task-search').value = '';
        document.getElementById('filter-priority').value = '';
        document.getElementById('filter-label').value = '';
        document.getElementById('filter-due-date').value = '';

        // Hide search clear button
        if (searchClear) {
            searchClear.classList.add('opacity-0');
            setTimeout(() => {
                searchClear.classList.add('hidden');
            }, 300);
        }

        // Show all tasks
        document.querySelectorAll('.task').forEach(task => {
            task.style.display = '';
        });

        // Hide all indicators
        const activeFiltersIndicator = document.getElementById('active-filters-indicator');
        const filterResults = document.getElementById('filter-results');
        const priorityIndicator = document.getElementById('priority-indicator');
        const labelIndicator = document.getElementById('label-indicator');
        const dueDateIndicator = document.getElementById('due-date-indicator');

        if (activeFiltersIndicator) {
            activeFiltersIndicator.classList.add('hidden');
        }

        if (filterResults) {
            filterResults.classList.add('hidden');
        }

        if (priorityIndicator) {
            priorityIndicator.classList.add('hidden');
        }

        if (labelIndicator) {
            labelIndicator.classList.add('hidden');
        }

        if (dueDateIndicator) {
            dueDateIndicator.classList.add('hidden');
        }

        // Add a visual feedback for the clear action
        clearFiltersButton.classList.add('bg-green-600');
        setTimeout(() => {
            clearFiltersButton.classList.remove('bg-green-600');
        }, 500);
    });

    // Filter tasks based on search input and filter options
    const filterTasks = () => {
        const searchText = document.getElementById('task-search').value.toLowerCase();
        const priorityFilter = document.getElementById('filter-priority').value.toLowerCase();
        const labelFilter = document.getElementById('filter-label').value.toLowerCase();
        const dueDateFilter = document.getElementById('filter-due-date').value;

        // Get UI elements for filter indicators
        const activeFiltersIndicator = document.getElementById('active-filters-indicator');
        const filterResults = document.getElementById('filter-results');
        const visibleTasksCount = document.getElementById('visible-tasks-count');
        const totalTasksCount = document.getElementById('total-tasks-count');

        // Get individual filter indicators
        const priorityIndicator = document.getElementById('priority-indicator');
        const priorityValue = document.getElementById('priority-value');
        const labelIndicator = document.getElementById('label-indicator');
        const labelValue = document.getElementById('label-value');
        const dueDateIndicator = document.getElementById('due-date-indicator');
        const dueDateValue = document.getElementById('due-date-value');

        // Check if any filters are active
        const hasActiveFilters = searchText !== '' || priorityFilter !== '' || labelFilter !== '' || dueDateFilter !== '';

        // Show/hide active filters indicator
        if (activeFiltersIndicator) {
            activeFiltersIndicator.classList.toggle('hidden', !hasActiveFilters);
        }

        // Update individual filter indicators
        if (priorityIndicator && priorityValue) {
            if (priorityFilter !== '') {
                priorityIndicator.classList.remove('hidden');
                priorityValue.textContent = priorityFilter.charAt(0).toUpperCase() + priorityFilter.slice(1);

                // Set color based on priority
                if (priorityFilter === 'high') {
                    priorityValue.className = 'text-red-400 font-bold';
                } else if (priorityFilter === 'medium') {
                    priorityValue.className = 'text-yellow-400 font-bold';
                } else if (priorityFilter === 'low') {
                    priorityValue.className = 'text-green-400 font-bold';
                } else {
                    priorityValue.className = 'text-white';
                }
            } else {
                priorityIndicator.classList.add('hidden');
            }
        }

        if (labelIndicator && labelValue) {
            if (labelFilter !== '') {
                labelIndicator.classList.remove('hidden');
                labelValue.textContent = labelFilter.charAt(0).toUpperCase() + labelFilter.slice(1);
            } else {
                labelIndicator.classList.add('hidden');
            }
        }

        if (dueDateIndicator && dueDateValue) {
            if (dueDateFilter !== '') {
                dueDateIndicator.classList.remove('hidden');

                // Format due date filter value for display
                let formattedDueDate = '';
                switch (dueDateFilter) {
                    case 'overdue':
                        formattedDueDate = 'Overdue';
                        dueDateValue.className = 'text-red-400 font-bold';
                        break;
                    case 'today':
                        formattedDueDate = 'Due Today';
                        dueDateValue.className = 'text-yellow-400 font-bold';
                        break;
                    case 'this-week':
                        formattedDueDate = 'Due This Week';
                        dueDateValue.className = 'text-indigo-400 font-bold';
                        break;
                    case 'next-week':
                        formattedDueDate = 'Due Next Week';
                        dueDateValue.className = 'text-blue-400 font-bold';
                        break;
                    case 'this-month':
                        formattedDueDate = 'Due This Month';
                        dueDateValue.className = 'text-purple-400 font-bold';
                        break;
                    case 'no-date':
                        formattedDueDate = 'No Due Date';
                        dueDateValue.className = 'text-gray-400 font-bold';
                        break;
                    default:
                        formattedDueDate = dueDateFilter;
                        dueDateValue.className = 'text-white';
                }

                dueDateValue.textContent = formattedDueDate;
            } else {
                dueDateIndicator.classList.add('hidden');
            }
        }

        let visibleCount = 0;
        const totalCount = document.querySelectorAll('.task').length;

        // Add a subtle animation to tasks when filtering
        document.querySelectorAll('.task').forEach(task => {
            // Save the original transform for later restoration
            if (!task.dataset.originalTransform) {
                task.dataset.originalTransform = task.style.transform || '';
            }

            const title = task.querySelector('h4').textContent.toLowerCase();
            const description = task.querySelector('.task-description')?.textContent.toLowerCase() || '';
            const priority = task.dataset.priority.toLowerCase();
            const label = task.dataset.label.toLowerCase();
            const isOverdue = task.hasAttribute('data-overdue');
            const isExpiring = task.hasAttribute('data-expiring');

            // Check if task matches search text
            const matchesSearch = searchText === '' ||
                title.includes(searchText) ||
                description.includes(searchText);

            // Check if task matches priority filter
            const matchesPriority = priorityFilter === '' || priority === priorityFilter;

            // Check if task matches label filter
            const matchesLabel = labelFilter === '' || label === labelFilter;

            // Check if task matches due date filter
            let matchesDueDate = true;
            if (dueDateFilter !== '') {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                const thisWeekStart = new Date(today);
                thisWeekStart.setDate(today.getDate() - today.getDay());

                const thisWeekEnd = new Date(thisWeekStart);
                thisWeekEnd.setDate(thisWeekStart.getDate() + 6);

                const nextWeekStart = new Date(thisWeekEnd);
                nextWeekStart.setDate(thisWeekEnd.getDate() + 1);

                const nextWeekEnd = new Date(nextWeekStart);
                nextWeekEnd.setDate(nextWeekStart.getDate() + 6);

                const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                const thisMonthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                switch (dueDateFilter) {
                    case 'overdue':
                        matchesDueDate = isOverdue;
                        break;
                    case 'today':
                        // Due today is handled by checking the due date text
                        const dueDateText = task.querySelector('.inline-block:nth-child(3)')?.textContent || '';
                        const todayFormatted = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        matchesDueDate = dueDateText.includes(todayFormatted);
                        break;
                    case 'this-week':
                        // This is an approximation - would be better to check actual date
                        matchesDueDate = isExpiring && !isOverdue;
                        break;
                    case 'no-date':
                        matchesDueDate = !task.querySelector('.inline-block:nth-child(3)');
                        break;
                    default:
                        matchesDueDate = true;
                }
            }

            // Show or hide task based on filters with animation
            if (matchesSearch && matchesPriority && matchesLabel && matchesDueDate) {
                // If task was previously hidden, add a subtle animation
                if (task.style.display === 'none') {
                    task.style.opacity = '0';
                    task.style.transform = `${task.dataset.originalTransform} translateY(10px)`;
                    task.style.display = '';

                    // Force reflow
                    void task.offsetWidth;

                    // Animate in
                    task.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                    task.style.opacity = '1';
                    task.style.transform = task.dataset.originalTransform;
                } else if (task.style.display === '') {
                    // Task is already visible, no animation needed
                    task.style.opacity = '1';
                    task.style.display = '';
                }

                visibleCount++;
            } else {
                // Fade out tasks that don't match
                task.style.transition = 'opacity 0.2s ease-out, transform 0.2s ease-out';
                task.style.opacity = '0';
                task.style.transform = `${task.dataset.originalTransform} translateY(-10px)`;

                // After animation completes, hide the element
                setTimeout(() => {
                    if (task.style.opacity === '0') {
                        task.style.display = 'none';
                    }
                }, 200);
            }
        });

        // Update task count display with animation
        if (visibleTasksCount && totalTasksCount) {
            // Animate the count change
            const currentCount = parseInt(visibleTasksCount.textContent);
            if (currentCount !== visibleCount) {
                // Add highlight effect
                visibleTasksCount.classList.add('text-indigo-400');

                // Update the count
                visibleTasksCount.textContent = visibleCount;
                totalTasksCount.textContent = totalCount;

                // Remove highlight after a delay
                setTimeout(() => {
                    visibleTasksCount.classList.remove('text-indigo-400');
                }, 1000);
            } else {
                // Just update without animation if count hasn't changed
                visibleTasksCount.textContent = visibleCount;
                totalTasksCount.textContent = totalCount;
            }

            // Show/hide filter results with animation
            if (filterResults) {
                if (hasActiveFilters && filterResults.classList.contains('hidden')) {
                    filterResults.classList.remove('hidden');
                    void filterResults.offsetWidth; // Force reflow
                    filterResults.classList.add('animate-fade-in');
                } else if (!hasActiveFilters && !filterResults.classList.contains('hidden')) {
                    filterResults.style.opacity = '0';
                    setTimeout(() => {
                        filterResults.classList.add('hidden');
                        filterResults.style.opacity = '';
                    }, 300);
                }
            }
        }

        // Update the filter button appearance based on active filters
        if (filterButton) {
            if (hasActiveFilters) {
                filterButton.classList.add('bg-indigo-700');
                filterButton.classList.add('ring-2');
                filterButton.classList.add('ring-indigo-400');
            } else {
                filterButton.classList.remove('bg-indigo-700');
                filterButton.classList.remove('ring-2');
                filterButton.classList.remove('ring-indigo-400');
            }
        }
    };

    // Add event listeners for search and filter inputs
    document.getElementById('task-search')?.addEventListener('input', filterTasks);
    document.getElementById('filter-priority')?.addEventListener('change', filterTasks);
    document.getElementById('filter-label')?.addEventListener('input', filterTasks);
    document.getElementById('filter-due-date')?.addEventListener('change', filterTasks);

    //
    // ─── TASK DETAIL MODAL ────────────────────────────────────────────────────────
    //

    const taskDetailModal = document.getElementById('task-detail-modal');
    const taskDetailTitle = document.getElementById('task-detail-title');
    const taskDetailDescription = document.getElementById('task-detail-description');
    const taskDetailLabel = document.getElementById('task-detail-label');
    const taskDetailPriority = document.getElementById('task-detail-priority');
    const taskDetailDueDate = document.getElementById('task-detail-due-date');
    const closeTaskDetail = document.getElementById('close-task-detail');
    const closeTaskDetailBtn = document.getElementById('close-task-detail-btn');
    const editTaskFromDetail = document.getElementById('edit-task-from-detail');

    // Add click event to all tasks to open detail modal
    document.querySelectorAll('.task').forEach(task => {
        task.addEventListener('click', (e) => {
            // Don't open detail modal if clicking on edit or delete buttons
            if (e.target.closest('.edit-task-button') || e.target.closest('.delete-task-button')) {
                return;
            }

            const taskId = task.dataset.taskId;
            const title = task.querySelector('h4').textContent.trim();
            const description = task.querySelector('.task-description')?.innerHTML || '';
            const priority = task.dataset.priority;
            const label = task.dataset.label;

            // Set task details in modal
            taskDetailTitle.textContent = title;
            taskDetailDescription.innerHTML = description;

            // Set label if it exists
            if (label) {
                const labelElement = task.querySelector('.inline-block:nth-child(2)');
                if (labelElement) {
                    taskDetailLabel.textContent = labelElement.textContent.trim();
                    taskDetailLabel.className = labelElement.className;
                    taskDetailLabel.classList.remove('hidden');
                } else {
                    taskDetailLabel.classList.add('hidden');
                }
            } else {
                taskDetailLabel.classList.add('hidden');
            }

            // Set priority if it exists
            if (priority) {
                const priorityElement = task.querySelector('.inline-block:nth-child(1)');
                if (priorityElement) {
                    taskDetailPriority.textContent = priorityElement.textContent.trim();
                    taskDetailPriority.className = priorityElement.className;
                    taskDetailPriority.classList.remove('hidden');
                } else {
                    taskDetailPriority.classList.add('hidden');
                }
            } else {
                taskDetailPriority.classList.add('hidden');
            }

            // Set due date if it exists
            const dueDateElement = task.querySelector('.inline-block:nth-child(3)');
            if (dueDateElement) {
                taskDetailDueDate.textContent = dueDateElement.textContent.trim();
                taskDetailDueDate.classList.remove('hidden');
            } else {
                taskDetailDueDate.classList.add('hidden');
            }

            // Store task ID for edit button
            editTaskFromDetail.dataset.taskId = taskId;

            // Show modal
            toggleModal(taskDetailModal, true);
        });
    });

    // Close detail modal
    closeTaskDetail?.addEventListener('click', () => {
        toggleModal(taskDetailModal, false);
    });

    closeTaskDetailBtn?.addEventListener('click', () => {
        toggleModal(taskDetailModal, false);
    });

    // Edit task from detail view
    editTaskFromDetail?.addEventListener('click', () => {
        toggleModal(taskDetailModal, false);

        // Find and click the edit button for this task
        const taskId = editTaskFromDetail.dataset.taskId;
        const editButton = document.querySelector(`.edit-task-button[data-task-id="${taskId}"]`);
        if (editButton) {
            editButton.click();
        }
    });
});
