<div class="lane bg-gray-800 rounded-xl p-5 min-w-[320px] max-w-[320px] flex flex-col h-full shadow-lg border border-gray-700" data-lane-id="{{ $lane->id }}">
    <div class="lane-header flex justify-between items-center mb-4 pb-3 border-b border-gray-700">
        <h3 class="text-lg font-semibold text-white">{{ $lane->name }}</h3>
        <div class="flex space-x-2">
            <button class="edit-lane-button text-gray-400 hover:text-indigo-400 transition-colors duration-200" data-lane-id="{{ $lane->id }}" data-lane-name="{{ $lane->name }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
            </button>
            <button class="delete-lane-button text-gray-400 hover:text-red-500 transition-colors duration-200" data-lane-id="{{ $lane->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    <div class="tasks-container flex-grow overflow-y-auto space-y-3 pr-1">
        @foreach($lane->tasks as $task)
            <x-tasks::task :task="$task" />
        @endforeach
    </div>

    <div class="mt-4 pt-3 border-t border-gray-700">
        <button class="add-task-button w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-all duration-200 flex items-center justify-center" data-lane-id="{{ $lane->id }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add Task
        </button>
    </div>
</div>
