<div class="lane bg-white/[0.03] border border-white/5 rounded-2xl flex flex-col min-w-[260px] max-w-[260px] h-full"
     data-lane-id="{{ $lane->id }}">

    {{-- Lane Header --}}
    <div class="lane-header flex items-center justify-between px-3.5 pt-3.5 pb-2.5 shrink-0">
        <div class="flex items-center gap-2 min-w-0">
            <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-widest truncate">{{ $lane->name }}</h3>
            <span class="text-xs text-gray-600 font-normal tabular-nums shrink-0">{{ $lane->tasks->count() }}</span>
        </div>
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 lane-actions">
            <button class="edit-lane-button text-gray-600 hover:text-gray-400 transition-colors p-1 rounded-lg hover:bg-white/5"
                    data-lane-id="{{ $lane->id }}"
                    data-lane-name="{{ $lane->name }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
            </button>
            <button class="delete-lane-button text-gray-600 hover:text-red-500/80 transition-colors p-1 rounded-lg hover:bg-white/5"
                    data-lane-id="{{ $lane->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Tasks --}}
    <div class="tasks-container flex-grow overflow-y-auto px-2 pb-2 space-y-1.5 min-h-[40px]">
        @foreach($lane->tasks as $task)
            <x-tasks::task :task="$task" />
        @endforeach
    </div>

    {{-- Add Task Footer --}}
    <div class="shrink-0 px-2 pb-2 pt-1">
        <button class="add-task-button w-full flex items-center gap-1.5 text-xs text-gray-600 hover:text-gray-400
                       px-3 py-2 rounded-xl hover:bg-white/5 transition-all duration-150 group"
                data-lane-id="{{ $lane->id }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add task
        </button>
    </div>
</div>
