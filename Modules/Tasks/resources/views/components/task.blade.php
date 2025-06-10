<div class="task bg-gray-700 hover:bg-gray-650 rounded-lg p-4 shadow-md cursor-move border border-gray-600 transition-all duration-200 transform hover:-translate-y-1"
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
                data-task-description="{{ htmlspecialchars($task->description) }}"
                data-task-label="{{ $task->label }}"
                data-task-due-date="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
                data-task-priority="{{ $task->priority }}"
                data-task-urls="{{ json_encode($task->urls) }}"
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

            @if($task->checklists->count() > 0)
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    {{ $task->checklists->count() }} {{ Str::plural('checklist', $task->checklists->count()) }}
                </span>
            @endif

            @if($task->dependencies->count() > 0)
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    {{ $task->dependencies->count() }} {{ Str::plural('dependency', $task->dependencies->count()) }}
                </span>
            @endif

            @if($task->urls && count($task->urls) > 0)
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    {{ count($task->urls) }} {{ Str::plural('URL', count($task->urls)) }}
                </span>
            @endif
        </div>

        {{-- Checklist Progress --}}
        @if($task->checklists->count() > 0)
            @php
                $totalItems = 0;
                $completedItems = 0;
                foreach ($task->checklists as $checklist) {
                    $totalItems += $checklist->items->count();
                    $completedItems += $checklist->items->where('is_completed', true)->count();
                }
                $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
            @endphp
            <div class="mt-2">
                <div class="flex justify-between text-xs text-gray-400 mb-1">
                    <span>Progress</span>
                    <span>{{ $completedItems }}/{{ $totalItems }}</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-1.5">
                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        @endif
    </div>
</div>
