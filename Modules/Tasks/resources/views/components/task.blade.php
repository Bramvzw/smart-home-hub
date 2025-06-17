<div class="task bg-gray-700 hover:bg-gray-650 rounded-lg p-4 shadow-md cursor-pointer border border-gray-600 transition-all duration-200 transform hover:-translate-y-1 hover:shadow-lg"
    data-task-id="{{ $task->id }}"
    data-lane-id="{{ $task->lane_id }}"
    data-label="{{ $task->label ?? '' }}"
    data-priority="{{ $task->priority ?? '' }}"
    @if($task->isOverdue()) data-overdue="true" @endif
    @if($task->isAboutToExpire()) data-expiring="true" @endif
>
    <div class="flex justify-between items-start">
        <h4 class="font-medium text-white @if($task->isOverdue()) text-red-400 @endif">
            {{ $task->title }}
            @if($task->isOverdue())
                <span class="ml-2 text-xs text-red-400 font-bold">OVERDUE</span>
            @elseif($task->isAboutToExpire())
                <span class="ml-2 text-xs text-yellow-400 font-bold">DUE SOON</span>
            @endif
        </h4>
        <div class="flex space-x-2 opacity-80 hover:opacity-100">
            <button class="edit-task-button text-gray-300 hover:text-indigo-400 transition-colors duration-200"
                    data-task-id="{{ $task->id }}"
                    data-task-title="{{ $task->title }}"
                    data-task-description="{{ e($task->description) }}"
                    data-task-label="{{ $task->label }}"
                    data-task-due-date="{{ optional($task->due_date)->format('Y-m-d') }}"
                    data-task-priority="{{ $task->priority }}"
                    data-task-notify="{{ $task->notify_before_expiry ? 'true' : 'false' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
            </button>
            <button class="delete-task-button text-gray-300 hover:text-red-500 transition-colors duration-200" data-task-id="{{ $task->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    @if($task->description)
        <div class="text-sm text-gray-300 mt-3 task-description">
            {!! $task->description !!}
        </div>
    @endif

    <div class="mt-3 space-y-2">
        {{-- Priority and Due Date --}}
        <div class="flex flex-wrap gap-2">
            @if($task->priority)
                @php
                    $priorityColors = [
                        'high' => 'bg-red-900 text-red-100',
                        'medium' => 'bg-yellow-900 text-yellow-100',
                        'low' => 'bg-green-900 text-green-100',
                    ];
                    $priorityClass = $priorityColors[strtolower($task->priority)] ?? 'bg-indigo-900 text-indigo-100';
                @endphp
                <span class="inline-block {{ $priorityClass }} text-xs px-2.5 py-1 rounded-full font-medium">
                    {{ ucfirst($task->priority) }} Priority
                </span>
            @endif

            @if($task->label)
                @php
                    $labelColors = [
                        'bug' => 'bg-red-900 text-red-100',
                        'feature' => 'bg-green-900 text-green-100',
                        'enhancement' => 'bg-blue-900 text-blue-100',
                        'documentation' => 'bg-yellow-900 text-yellow-100',
                        'question' => 'bg-purple-900 text-purple-100',
                    ];
                    $labelClass = $labelColors[strtolower($task->label)] ?? 'bg-indigo-900 text-indigo-100';
                @endphp
                <span class="inline-block {{ $labelClass }} text-xs px-2.5 py-1 rounded-full font-medium">{{ $task->label }}</span>
            @endif

            @if($task->due_date)
                <span class="inline-block bg-gray-800 text-gray-200 text-xs px-2.5 py-1 rounded-full font-medium">
                    Due: {{ $task->due_date->format('M d, Y') }}
                </span>
            @endif
        </div>

        {{-- Indicators for attachments, checklists, dependencies, and URLs --}}
        <div class="flex flex-wrap gap-2 text-xs text-gray-300">
            @if($task->attachments->count() > 0)
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ $task->attachments->count() }} {{ Str::plural('attachment', $task->attachments->count()) }}
                </span>
            @endif
        </div>
    </div>
</div>
