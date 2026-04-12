<x-dashboard.layout title="Tasks Board">
    <x-slot:scripts>
        @vite(['Modules/Tasks/resources/assets/js/tasks-board.js', 'Modules/Tasks/resources/assets/css/tasks.css'])
    </x-slot:scripts>

    <div class="flex flex-col h-full overflow-hidden text-white">
        <div class="flex flex-grow overflow-hidden">
            <div class="w-full p-6 flex flex-col overflow-hidden">
                <div id="tasks-board" class="p-4 flex flex-col flex-grow overflow-hidden">
                    <div class="flex flex-col space-y-4 mb-6">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <h1 class="text-3xl font-bold text-white">Tasks Board</h1>
                                <a href="{{ route('tasks.notifications') }}" class="text-indigo-400 hover:text-indigo-300">Notifications</a>
                            </div>

                            {{-- Add Lane Button --}}
                            <button id="add-lane-button" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-md shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Add Lane
                            </button>
                        </div>

                        {{-- Search and Filter --}}
                        <div class="bg-gray-800 rounded-lg p-5 shadow-lg border border-gray-700 transform transition-all duration-300">
                            <div class="flex flex-col md:flex-row md:items-center space-y-3 md:space-y-0 md:space-x-4">
                                <div class="flex-grow">
                                    <div class="relative group">
                                        <input type="text" id="task-search" placeholder="Search tasks..."
                                            class="w-full pl-12 pr-4 py-3 bg-gray-700 border-2 border-gray-600 text-white rounded-lg
                                            focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                            transition-all duration-300 shadow-sm hover:shadow-md">
                                        <div class="absolute left-3 top-3 text-gray-400 group-hover:text-indigo-400 transition-colors duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <div id="search-clear" class="absolute right-3 top-3 text-gray-500 hover:text-gray-300 cursor-pointer opacity-0 transition-opacity duration-300 hidden">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div id="filter-results" class="text-sm text-indigo-300 font-medium mt-2 hidden animate-fade-in">
                                        Showing <span id="visible-tasks-count" class="font-bold">0</span> of <span id="total-tasks-count">0</span> tasks
                                    </div>
                                </div>

                                <div class="flex space-x-3">
                                    <button id="filter-button"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-5 rounded-lg
                                        shadow-sm hover:shadow-md transition-all duration-300 transform hover:scale-105 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                        Filters
                                        <span id="active-filters-indicator" class="ml-2 hidden w-3 h-3 bg-white rounded-full animate-pulse"></span>
                                    </button>

                                    <button id="clear-filters-button"
                                        class="bg-gray-700 hover:bg-gray-600 text-white font-medium py-3 px-5 rounded-lg
                                        shadow-sm hover:shadow-md transition-all duration-300 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Clear All
                                    </button>
                                </div>
                            </div>

                            {{-- Filter Options (Hidden by default) --}}
                            <div id="filter-options" class="hidden mt-5 pt-5 border-t border-gray-700 grid grid-cols-1 md:grid-cols-3 gap-5 animate-fade-in">
                                <div class="bg-gray-750 p-4 rounded-lg shadow-inner">
                                    <label for="filter-priority" class="block text-indigo-300 font-medium mb-2">Priority</label>
                                    <select id="filter-priority"
                                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 text-white rounded-lg
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                        transition-all duration-300 shadow-sm">
                                        <option value="">All Priorities</option>
                                        <option value="high" class="bg-red-900 text-white">High</option>
                                        <option value="medium" class="bg-yellow-900 text-white">Medium</option>
                                        <option value="low" class="bg-green-900 text-white">Low</option>
                                    </select>
                                    <div id="priority-indicator" class="mt-2 hidden">
                                        <span class="text-xs font-medium text-indigo-300">Active filter: <span id="priority-value" class="text-white">None</span></span>
                                    </div>
                                </div>

                                <div class="bg-gray-750 p-4 rounded-lg shadow-inner">
                                    <label for="filter-label" class="block text-indigo-300 font-medium mb-2">Label</label>
                                    <input type="text" id="filter-label" list="label-options"
                                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 text-white rounded-lg
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                        transition-all duration-300 shadow-sm"
                                        placeholder="All Labels">
                                    <div id="label-indicator" class="mt-2 hidden">
                                        <span class="text-xs font-medium text-indigo-300">Active filter: <span id="label-value" class="text-white">None</span></span>
                                    </div>
                                </div>

                                <div class="bg-gray-750 p-4 rounded-lg shadow-inner">
                                    <label for="filter-due-date" class="block text-indigo-300 font-medium mb-2">Due Date</label>
                                    <select id="filter-due-date"
                                        class="w-full px-4 py-3 bg-gray-700 border-2 border-gray-600 text-white rounded-lg
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                        transition-all duration-300 shadow-sm">
                                        <option value="">All Due Dates</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="today">Due Today</option>
                                        <option value="this-week">Due This Week</option>
                                        <option value="next-week">Due Next Week</option>
                                        <option value="this-month">Due This Month</option>
                                        <option value="no-date">No Due Date</option>
                                    </select>
                                    <div id="due-date-indicator" class="mt-2 hidden">
                                        <span class="text-xs font-medium text-indigo-300">Active filter: <span id="due-date-value" class="text-white">None</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Lanes --}}
                    <div id="lanes-container" class="flex flex-grow overflow-x-auto space-x-6 pb-4">
                        @foreach($lanes as $lane)
                            <x-tasks::lane :lane="$lane" />
                        @endforeach

                        {{-- Empty State --}}
                        @if($lanes->isEmpty())
                            <div class="flex items-center justify-center w-full h-full">
                                <div class="text-center bg-gray-800 bg-opacity-50 p-8 rounded-xl shadow-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-indigo-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <h2 class="text-2xl font-semibold mb-3 text-white">No lanes yet</h2>
                                    <p class="text-gray-300">Click the "Add Lane" button to create your first lane.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Lane Modal --}}
    <div id="add-lane-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden backdrop-blur-sm z-50 transition-opacity duration-300">
        <div class="bg-gray-800 p-6 rounded-xl shadow-2xl w-96 border border-gray-700 transform transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-white">Add Lane</h2>
                <button type="button" id="cancel-add-lane-x" class="cancel-add-lane text-gray-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="add-lane-form">
                <div class="mb-6">
                    <label for="lane-name" class="block text-gray-300 font-medium mb-2">Lane Name</label>
                    <input type="text" id="lane-name" name="name" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" placeholder="Enter lane name..." required>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-add-lane-btn" class="cancel-add-lane bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium py-2 px-5 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm hover:shadow-md transition-all transform hover:scale-105">
                        Add Lane
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Task Modal --}}
    <div id="add-task-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden backdrop-blur-sm z-50 transition-opacity duration-300">
        <div class="bg-gray-800 p-6 rounded-xl shadow-2xl w-[500px] max-h-[90vh] overflow-y-auto border border-gray-700 transform transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-white">Add Task</h2>
                <button type="button" id="cancel-add-task" class="text-gray-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="add-task-form">
                <input type="hidden" id="task-lane-id" name="lane_id">

                <div class="mb-5">
                    <label for="task-title" class="block text-gray-300 font-medium mb-2">Title</label>
                    <input type="text" id="task-title" name="title" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" placeholder="Enter task title..." required>
                </div>

                <div class="mb-5">
                    <label for="task-description" class="block text-gray-300 font-medium mb-2">Description</label>
                    <textarea id="task-description" name="description" class="task-description w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" placeholder="Enter task description..."></textarea>
                    <div class="mt-1 text-xs text-gray-400">
                        <p>Keyboard shortcuts: <span class="bg-gray-700 px-1 rounded">⌘B</span> Bold, <span class="bg-gray-700 px-1 rounded">⌘I</span> Italic, <span class="bg-gray-700 px-1 rounded">⌘K</span> Link, <span class="bg-gray-700 px-1 rounded">⌘⇧7</span> Numbered list, <span class="bg-gray-700 px-1 rounded">⌘⇧8</span> Bullet list, <span class="bg-gray-700 px-1 rounded">⌘⇧9</span> Checklist</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label for="task-label" class="block text-gray-300 font-medium mb-2">Label</label>
                        <input type="text" id="task-label" name="label" list="label-options" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" placeholder="None">
                    </div>

                    <div>
                        <label for="task-priority" class="block text-gray-300 font-medium mb-2">Priority</label>
                        <select id="task-priority" name="priority" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                            <option value="">None</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="mb-5">
                    <label for="task-due-date" class="block text-gray-300 font-medium mb-2">Due Date</label>
                    <input type="date" id="task-due-date" name="due_date" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                </div>

                <div class="mb-5">
                    <div class="flex items-center">
                        <input type="checkbox" id="task-notify" name="notify_before_expiry" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-600 rounded bg-gray-700">
                        <label for="task-notify" class="ml-2 block text-gray-300 font-medium">Notify before expiry</label>
                    </div>
                </div>


                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-add-task-btn" class="bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium py-2 px-5 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm hover:shadow-md transition-all transform hover:scale-105">
                        Add Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Task Modal --}}
    <div id="edit-task-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden backdrop-blur-sm z-50 transition-opacity duration-300">
        <div class="bg-gray-800 p-6 rounded-xl shadow-2xl w-[500px] max-h-[90vh] overflow-y-auto border border-gray-700 transform transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-white">Edit Task</h2>
                <button type="button" id="cancel-edit-task" class="text-gray-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit-task-form">
                <input type="hidden" id="edit-task-id" name="id">

                <div class="mb-5">
                    <label for="edit-task-title" class="block text-gray-300 font-medium mb-2">Title</label>
                    <input type="text" id="edit-task-title" name="title" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" required>
                </div>

                <div class="mb-5">
                    <label for="edit-task-description" class="block text-gray-300 font-medium mb-2">Description</label>
                    <textarea id="edit-task-description" name="description" class="task-description w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors"></textarea>
                    <div class="mt-1 text-xs text-gray-400">
                        <p>Keyboard shortcuts: <span class="bg-gray-700 px-1 rounded">⌘B</span> Bold, <span class="bg-gray-700 px-1 rounded">⌘I</span> Italic, <span class="bg-gray-700 px-1 rounded">⌘K</span> Link, <span class="bg-gray-700 px-1 rounded">⌘⇧7</span> Numbered list, <span class="bg-gray-700 px-1 rounded">⌘⇧8</span> Bullet list, <span class="bg-gray-700 px-1 rounded">⌘⇧9</span> Checklist</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label for="edit-task-label" class="block text-gray-300 font-medium mb-2">Label</label>
                        <input type="text" id="edit-task-label" name="label" list="label-options" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" placeholder="None">
                    </div>

                    <div>
                        <label for="edit-task-priority" class="block text-gray-300 font-medium mb-2">Priority</label>
                        <select id="edit-task-priority" name="priority" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                            <option value="">None</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="mb-5">
                    <label for="edit-task-due-date" class="block text-gray-300 font-medium mb-2">Due Date</label>
                    <input type="date" id="edit-task-due-date" name="due_date" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors">
                </div>

                <div class="mb-5">
                    <div class="flex items-center">
                        <input type="checkbox" id="edit-task-notify" name="notify_before_expiry" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-600 rounded bg-gray-700">
                        <label for="edit-task-notify" class="ml-2 block text-gray-300 font-medium">Notify before expiry</label>
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-gray-300 font-medium mb-2">Attachments</label>
                    <div id="task-attachments-container" class="space-y-2 mb-2">
                        <!-- Attachments will be listed here dynamically -->
                    </div>
                    <div class="flex items-center space-x-2">
                        <label for="attachment-file-input" class="cursor-pointer bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium py-2 px-4 rounded-lg transition-colors text-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            Upload File
                        </label>
                        <input id="attachment-file-input" type="file" class="hidden">
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-edit-task-btn" class="bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium py-2 px-5 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm hover:shadow-md transition-all transform hover:scale-105">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Lane Modal --}}
    <div id="edit-lane-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden backdrop-blur-sm z-50 transition-opacity duration-300">
        <div class="bg-gray-800 p-6 rounded-xl shadow-2xl w-96 border border-gray-700 transform transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-white">Edit Lane</h2>
                <button type="button" id="cancel-edit-lane" class="text-gray-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="edit-lane-form">
                <input type="hidden" id="edit-lane-id" name="id">
                <div class="mb-6">
                    <label for="edit-lane-name" class="block text-gray-300 font-medium mb-2">Lane Name</label>
                    <input type="text" id="edit-lane-name" name="name" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" placeholder="Enter lane name..." required>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-edit-lane-btn" class="bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium py-2 px-5 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm hover:shadow-md transition-all transform hover:scale-105">
                        Save Changes
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
    <div id="task-detail-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden backdrop-blur-sm z-50 transition-opacity duration-300">
        <div class="bg-gray-800 p-6 rounded-xl shadow-2xl w-[500px] max-h-[90vh] overflow-y-auto border border-gray-700 transform transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 id="task-detail-title" class="text-xl font-bold text-white">Task Title</h2>
                <button type="button" id="close-task-detail" class="text-gray-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-6">
                <div id="task-detail-description" class="text-gray-300 prose prose-sm prose-invert max-w-none">
                    <!-- Task description will be inserted here -->
                </div>
            </div>

            <div class="mb-6 space-y-4">
                <div id="task-detail-label" class="hidden px-3 py-1 bg-indigo-900 text-indigo-100 text-sm rounded-full inline-block">
                    <!-- Label will be inserted here -->
                </div>

                <div id="task-detail-priority" class="hidden px-3 py-1 bg-red-900 text-red-100 text-sm rounded-full inline-block">
                    <!-- Priority will be inserted here -->
                </div>

                <div id="task-detail-due-date" class="hidden px-3 py-1 bg-gray-700 text-gray-200 text-sm rounded-full inline-block">
                    <!-- Due date will be inserted here -->
                </div>
            </div>


            <div class="flex justify-end space-x-3">
                <button type="button" id="close-task-detail-btn" class="bg-gray-700 hover:bg-gray-600 text-gray-300 font-medium py-2 px-5 rounded-lg transition-colors">
                    Close
                </button>
                <button type="button" id="edit-task-from-detail" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm hover:shadow-md transition-all transform hover:scale-105">
                    Edit Task
                </button>
            </div>
        </div>
    </div>
</x-dashboard.layout>
