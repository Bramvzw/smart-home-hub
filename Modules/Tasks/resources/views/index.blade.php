<x-dashboard.layout title="Tasks Board">
    <x-slot:scripts>
        @vite(['Modules/Tasks/resources/assets/js/tasks-board.js', 'Modules/Tasks/resources/assets/css/tasks.css'])
    </x-slot:scripts>
    <x-slot:head>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    </x-slot:head>

    <div class="tasks-ui flex flex-col h-full overflow-hidden text-white">
        <div class="flex flex-grow overflow-hidden">
            <div class="w-full flex flex-col overflow-hidden">
                <div id="tasks-board" class="flex flex-col flex-grow overflow-hidden">

                    {{-- Header --}}
                    <div class="px-5 pt-5 pb-4 border-b border-white/5 shrink-0">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <h1 class="text-xl font-semibold text-white tracking-tight">Tasks</h1>
                                <a href="{{ route('tasks.notifications') }}"
                                   class="text-xs text-gray-500 hover:text-gray-300 transition-colors duration-150">
                                    Notifications
                                </a>
                            </div>
                            <button id="add-lane-button"
                                class="flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-white
                                       bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10
                                       px-3 py-1.5 rounded-lg transition-all duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Add lane
                            </button>
                        </div>

                        {{-- Search and Filter Bar --}}
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1 max-w-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-600 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" id="task-search" placeholder="Search tasks…"
                                    class="w-full pl-8 pr-7 py-1.5 bg-white/5 border border-white/5 text-sm text-white placeholder-gray-600
                                           rounded-lg focus:outline-none focus:border-white/15 focus:bg-white/8 transition-all duration-150">
                                <button id="search-clear" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 opacity-0 hidden transition-opacity duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <button id="filter-button"
                                class="flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-white
                                       bg-white/5 hover:bg-white/8 border border-white/5
                                       px-3 py-1.5 rounded-lg transition-all duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filter
                                <span id="active-filters-indicator" class="hidden w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                            </button>

                            <button id="clear-filters-button"
                                class="text-xs text-gray-600 hover:text-gray-400 px-2 py-1.5 transition-colors duration-150">
                                Clear
                            </button>

                            <div id="filter-results" class="hidden text-xs text-gray-600 ml-1">
                                <span id="visible-tasks-count" class="text-gray-400 font-medium">0</span>
                                <span> / </span>
                                <span id="total-tasks-count">0</span>
                            </div>
                        </div>

                        {{-- Filter Options (hidden by default) --}}
                        <div id="filter-options" class="hidden mt-3 pt-3 border-t border-white/5 grid grid-cols-3 gap-3 animate-fade-in">
                            <div>
                                <label for="filter-priority" class="block text-xs text-gray-500 mb-1.5">Priority</label>
                                <select id="filter-priority"
                                    class="w-full px-2.5 py-1.5 bg-white/5 border border-white/5 text-sm text-white rounded-lg
                                           focus:outline-none focus:border-white/15 transition-all duration-150 appearance-none">
                                    <option value="">All</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <div id="priority-indicator" class="mt-1 hidden text-xs text-gray-500">
                                    Active: <span id="priority-value" class="text-white">None</span>
                                </div>
                            </div>

                            <div>
                                <label for="filter-label" class="block text-xs text-gray-500 mb-1.5">Label</label>
                                <input type="text" id="filter-label" list="label-options"
                                    class="w-full px-2.5 py-1.5 bg-white/5 border border-white/5 text-sm text-white rounded-lg
                                           focus:outline-none focus:border-white/15 transition-all duration-150 placeholder-gray-600"
                                    placeholder="All labels">
                                <div id="label-indicator" class="mt-1 hidden text-xs text-gray-500">
                                    Active: <span id="label-value" class="text-white">None</span>
                                </div>
                            </div>

                            <div>
                                <label for="filter-due-date" class="block text-xs text-gray-500 mb-1.5">Due Date</label>
                                <select id="filter-due-date"
                                    class="w-full px-2.5 py-1.5 bg-white/5 border border-white/5 text-sm text-white rounded-lg
                                           focus:outline-none focus:border-white/15 transition-all duration-150 appearance-none">
                                    <option value="">All</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="today">Due Today</option>
                                    <option value="this-week">This Week</option>
                                    <option value="next-week">Next Week</option>
                                    <option value="this-month">This Month</option>
                                    <option value="no-date">No Date</option>
                                </select>
                                <div id="due-date-indicator" class="mt-1 hidden text-xs text-gray-500">
                                    Active: <span id="due-date-value" class="text-white">None</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Lanes Container --}}
                    <div id="lanes-container" class="flex flex-grow overflow-x-auto gap-3 p-4">
                        @foreach($lanes as $lane)
                            <x-tasks::lane :lane="$lane" />
                        @endforeach

                        @if($lanes->isEmpty())
                            <div class="flex items-center justify-center w-full h-full">
                                <div class="text-center">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-2xl bg-white/5 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">No lanes yet</p>
                                    <p class="text-xs text-gray-600 mt-1">Add a lane to get started</p>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Add Lane Modal --}}
    <div id="add-lane-modal" class="fixed inset-0 bg-black/75 flex items-center justify-center hidden backdrop-blur-sm z-50">
        <div class="tasks-modal bg-[#141414] border border-white/8 rounded-2xl shadow-2xl w-80 p-5">
            <div class="flex justify-between items-center mb-5">
                <h2 class="text-sm font-semibold text-white">Add lane</h2>
                <button type="button" id="cancel-add-lane-x" class="cancel-add-lane text-gray-600 hover:text-gray-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="add-lane-form">
                <div class="mb-4">
                    <label for="lane-name" class="block text-xs text-gray-500 mb-1.5">Lane name</label>
                    <input type="text" id="lane-name" name="name"
                        class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors placeholder-gray-600"
                        placeholder="e.g. To Do, In Progress…" required>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" id="cancel-add-lane-btn" class="cancel-add-lane text-xs text-gray-500 hover:text-gray-300 px-3 py-2 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="text-xs font-medium bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-xl transition-all duration-150">
                        Add lane
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Task Modal --}}
    <div id="add-task-modal" class="fixed inset-0 bg-black/75 flex items-center justify-center hidden backdrop-blur-sm z-50">
        <div class="tasks-modal bg-[#141414] border border-white/8 rounded-2xl shadow-2xl w-[480px] max-h-[90vh] overflow-y-auto p-5">
            <div class="flex justify-between items-center mb-5">
                <h2 class="text-sm font-semibold text-white">Add task</h2>
                <button type="button" id="cancel-add-task" class="text-gray-600 hover:text-gray-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="add-task-form">
                <input type="hidden" id="task-lane-id" name="lane_id">

                <div class="mb-4">
                    <label for="task-title" class="block text-xs text-gray-500 mb-1.5">Title</label>
                    <input type="text" id="task-title" name="title"
                        class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors placeholder-gray-600"
                        placeholder="Task title…" required>
                </div>

                <div class="mb-4">
                    <label for="task-description" class="block text-xs text-gray-500 mb-1.5">Description</label>
                    <textarea id="task-description" name="description"
                        class="task-description w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors"
                        placeholder="Add a description…"></textarea>
                    <div class="mt-1 text-xs text-gray-600">
                        <span class="bg-white/5 px-1 rounded">⌘B</span> Bold &middot;
                        <span class="bg-white/5 px-1 rounded">⌘I</span> Italic &middot;
                        <span class="bg-white/5 px-1 rounded">⌘K</span> Link
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label for="task-label" class="block text-xs text-gray-500 mb-1.5">Label</label>
                        <input type="text" id="task-label" name="label" list="label-options"
                            class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                                   focus:outline-none focus:border-white/20 transition-colors placeholder-gray-600"
                            placeholder="None">
                    </div>
                    <div>
                        <label for="task-priority" class="block text-xs text-gray-500 mb-1.5">Priority</label>
                        <select id="task-priority" name="priority"
                            class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                                   focus:outline-none focus:border-white/20 transition-colors appearance-none">
                            <option value="">None</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="task-due-date" class="block text-xs text-gray-500 mb-1.5">Due date</label>
                    <input type="date" id="task-due-date" name="due_date"
                        class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors">
                </div>

                <div class="mb-5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="task-notify" name="notify_before_expiry"
                            class="w-3.5 h-3.5 rounded bg-white/5 border-white/10 text-green-400 focus:ring-0 focus:ring-offset-0">
                        <span class="text-xs text-gray-400">Notify before expiry</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-white/5">
                    <button type="button" id="cancel-add-task-btn" class="text-xs text-gray-500 hover:text-gray-300 px-3 py-2 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="text-xs font-medium bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-xl transition-all duration-150">
                        Add task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Task Modal --}}
    <div id="edit-task-modal" class="fixed inset-0 bg-black/75 flex items-center justify-center hidden backdrop-blur-sm z-50">
        <div class="tasks-modal bg-[#141414] border border-white/8 rounded-2xl shadow-2xl w-[480px] max-h-[90vh] overflow-y-auto p-5">
            <div class="flex justify-between items-center mb-5">
                <h2 class="text-sm font-semibold text-white">Edit task</h2>
                <button type="button" id="cancel-edit-task" class="text-gray-600 hover:text-gray-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit-task-form">
                <input type="hidden" id="edit-task-id" name="id">

                <div class="mb-4">
                    <label for="edit-task-title" class="block text-xs text-gray-500 mb-1.5">Title</label>
                    <input type="text" id="edit-task-title" name="title"
                        class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors"
                        required>
                </div>

                <div class="mb-4">
                    <label for="edit-task-description" class="block text-xs text-gray-500 mb-1.5">Description</label>
                    <textarea id="edit-task-description" name="description"
                        class="task-description w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors"></textarea>
                    <div class="mt-1 text-xs text-gray-600">
                        <span class="bg-white/5 px-1 rounded">⌘B</span> Bold &middot;
                        <span class="bg-white/5 px-1 rounded">⌘I</span> Italic &middot;
                        <span class="bg-white/5 px-1 rounded">⌘K</span> Link
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label for="edit-task-label" class="block text-xs text-gray-500 mb-1.5">Label</label>
                        <input type="text" id="edit-task-label" name="label" list="label-options"
                            class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                                   focus:outline-none focus:border-white/20 transition-colors placeholder-gray-600"
                            placeholder="None">
                    </div>
                    <div>
                        <label for="edit-task-priority" class="block text-xs text-gray-500 mb-1.5">Priority</label>
                        <select id="edit-task-priority" name="priority"
                            class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                                   focus:outline-none focus:border-white/20 transition-colors appearance-none">
                            <option value="">None</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="edit-task-due-date" class="block text-xs text-gray-500 mb-1.5">Due date</label>
                    <input type="date" id="edit-task-due-date" name="due_date"
                        class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors">
                </div>

                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit-task-notify" name="notify_before_expiry"
                            class="w-3.5 h-3.5 rounded bg-white/5 border-white/10 text-green-400 focus:ring-0 focus:ring-offset-0">
                        <span class="text-xs text-gray-400">Notify before expiry</span>
                    </label>
                </div>

                <div class="mb-4">
                    <label class="block text-xs text-gray-500 mb-2">Attachments</label>
                    <div id="task-attachments-container" class="space-y-1.5 mb-2">
                        {{-- Attachments injected dynamically --}}
                    </div>
                    <label for="attachment-file-input"
                        class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-300
                               bg-white/5 hover:bg-white/8 border border-white/5 px-3 py-1.5 rounded-lg cursor-pointer transition-all duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        Upload file
                    </label>
                    <input id="attachment-file-input" type="file" class="hidden">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-white/5">
                    <button type="button" id="cancel-edit-task-btn" class="text-xs text-gray-500 hover:text-gray-300 px-3 py-2 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="text-xs font-medium bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-xl transition-all duration-150">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Lane Modal --}}
    <div id="edit-lane-modal" class="fixed inset-0 bg-black/75 flex items-center justify-center hidden backdrop-blur-sm z-50">
        <div class="tasks-modal bg-[#141414] border border-white/8 rounded-2xl shadow-2xl w-80 p-5">
            <div class="flex justify-between items-center mb-5">
                <h2 class="text-sm font-semibold text-white">Edit lane</h2>
                <button type="button" id="cancel-edit-lane" class="text-gray-600 hover:text-gray-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit-lane-form">
                <input type="hidden" id="edit-lane-id" name="id">
                <div class="mb-4">
                    <label for="edit-lane-name" class="block text-xs text-gray-500 mb-1.5">Lane name</label>
                    <input type="text" id="edit-lane-name" name="name"
                        class="w-full px-3 py-2 bg-white/5 border border-white/8 text-sm text-white rounded-xl
                               focus:outline-none focus:border-white/20 transition-colors placeholder-gray-600"
                        placeholder="Lane name…" required>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-white/5">
                    <button type="button" id="cancel-edit-lane-btn" class="text-xs text-gray-500 hover:text-gray-300 px-3 py-2 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="text-xs font-medium bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-xl transition-all duration-150">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <datalist id="label-options">
        <option value="bug">
        <option value="feature">
        <option value="enhancement">
        <option value="documentation">
        <option value="question">
    </datalist>

    {{-- Task Detail Modal --}}
    <div id="task-detail-modal" class="fixed inset-0 bg-black/75 flex items-center justify-center hidden backdrop-blur-sm z-50">
        <div class="tasks-modal bg-[#141414] border border-white/8 rounded-2xl shadow-2xl w-[480px] max-h-[90vh] overflow-y-auto p-5">
            <div class="flex justify-between items-start mb-4">
                <h2 id="task-detail-title" class="text-base font-semibold text-white leading-snug pr-4">Task Title</h2>
                <button type="button" id="close-task-detail" class="text-gray-600 hover:text-gray-400 transition-colors shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex flex-wrap gap-1.5 mb-4">
                <div id="task-detail-label" class="hidden text-xs px-2 py-0.5 rounded-md bg-white/8 text-gray-300 border border-white/8"></div>
                <div id="task-detail-priority" class="hidden text-xs px-2 py-0.5 rounded-md bg-red-500/15 text-red-400 border border-red-500/20"></div>
                <div id="task-detail-due-date" class="hidden text-xs px-2 py-0.5 rounded-md bg-white/5 text-gray-400 border border-white/8"></div>
            </div>

            <div id="task-detail-description" class="text-sm text-gray-400 prose prose-sm prose-invert max-w-none mb-6">
                {{-- Description injected dynamically --}}
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-white/5">
                <button type="button" id="close-task-detail-btn" class="text-xs text-gray-500 hover:text-gray-300 px-3 py-2 transition-colors">
                    Close
                </button>
                <button type="button" id="edit-task-from-detail"
                    class="text-xs font-medium bg-white/10 hover:bg-white/15 text-white px-4 py-2 rounded-xl transition-all duration-150">
                    Edit task
                </button>
            </div>
        </div>
    </div>
</x-dashboard.layout>
