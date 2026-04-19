@php
    $priorityDot = [
        'high'   => 'bg-red-500',
        'medium' => 'bg-amber-400',
        'low'    => 'bg-green-500',
    ];
    $labelColors = [
        'bug'           => 'bg-red-500/15 text-red-400 border-red-500/20',
        'feature'       => 'bg-green-500/15 text-green-400 border-green-500/20',
        'enhancement'   => 'bg-blue-500/15 text-blue-400 border-blue-500/20',
        'documentation' => 'bg-amber-500/15 text-amber-400 border-amber-500/20',
        'question'      => 'bg-purple-500/15 text-purple-400 border-purple-500/20',
    ];
    $dotClass   = $priorityDot[strtolower($task->priority ?? '')] ?? null;
    $labelClass = $labelColors[strtolower($task->label ?? '')] ?? 'bg-white/8 text-gray-400 border-white/8';
@endphp

<div class="task group bg-white/[0.03] hover:bg-white/[0.06] border border-white/5 hover:border-white/10
            rounded-xl px-3 py-2.5 cursor-pointer transition-all duration-150"
    data-task-id="{{ $task->id }}"
    data-lane-id="{{ $task->lane_id }}"
    data-label="{{ $task->label ?? '' }}"
    data-priority="{{ $task->priority ?? '' }}"
    @if($task->isOverdue()) data-overdue="true" @endif
    @if($task->isAboutToExpire()) data-expiring="true" @endif
>
    {{-- Title row --}}
    <div class="flex items-start justify-between gap-2">
        <h4 class="text-sm font-medium leading-snug @if($task->isOverdue()) text-red-400 @else text-white @endif flex-1 min-w-0">
            {{ $task->title }}
        </h4>
        {{-- Action buttons (hidden until hover) --}}
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150 shrink-0 -mt-0.5">
            <button class="edit-task-button text-gray-600 hover:text-gray-400 transition-colors p-1 rounded-lg hover:bg-white/8"
                    data-task-id="{{ $task->id }}"
                    data-task-title="{{ $task->title }}"
                    data-task-description="{{ e($task->description) }}"
                    data-task-label="{{ $task->label }}"
                    data-task-due-date="{{ optional($task->due_date)->format('Y-m-d') }}"
                    data-task-priority="{{ $task->priority }}"
                    data-task-notify="{{ $task->notify_before_expiry ? 'true' : 'false' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
            </button>
            <button class="delete-task-button text-gray-600 hover:text-red-500/70 transition-colors p-1 rounded-lg hover:bg-white/8"
                    data-task-id="{{ $task->id }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Description snippet (hidden, used by JS for detail modal) --}}
    @if($task->description)
        <div class="task-description hidden">
            {!! $task->description !!}
        </div>
    @endif

    {{-- Meta row --}}
    @php
        $hasMeta = $task->priority || $task->label || $task->due_date || $task->attachments->count() > 0 || $task->isOverdue() || $task->isAboutToExpire();
    @endphp
    @if($hasMeta)
        <div class="flex flex-wrap items-center gap-1.5 mt-2">

            {{-- Priority dot + label badge (first two .inline-block children the JS looks for) --}}
            @if($task->priority)
                @php
                    $priorityBadgeColors = [
                        'high'   => 'bg-red-500/15 text-red-400 border-red-500/20',
                        'medium' => 'bg-amber-500/15 text-amber-400 border-amber-500/20',
                        'low'    => 'bg-green-500/15 text-green-400 border-green-500/20',
                    ];
                    $pClass = $priorityBadgeColors[strtolower($task->priority)] ?? 'bg-white/8 text-gray-400 border-white/8';
                @endphp
                <span class="inline-block {{ $pClass }} text-xs px-1.5 py-0.5 rounded-md border font-medium">
                    {{ ucfirst($task->priority) }}
                </span>
            @endif

            @if($task->label)
                <span class="inline-block {{ $labelClass }} text-xs px-1.5 py-0.5 rounded-md border">{{ $task->label }}</span>
            @endif

            @if($task->due_date)
                <span class="inline-block bg-white/5 text-gray-500 border border-white/5 text-xs px-1.5 py-0.5 rounded-md">
                    {{ $task->due_date->format('M j') }}
                </span>
            @endif

            @if($task->isOverdue())
                <span class="text-xs text-red-500/80 font-medium">Overdue</span>
            @elseif($task->isAboutToExpire())
                <span class="text-xs text-amber-400/80 font-medium">Due soon</span>
            @endif

            @if($task->attachments->count() > 0)
                <span class="flex items-center gap-0.5 text-xs text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ $task->attachments->count() }}
                </span>
            @endif
        </div>
    @endif
</div>
