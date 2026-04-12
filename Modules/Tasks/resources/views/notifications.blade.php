<x-dashboard.layout title="Task Notifications">
<div class="p-6">
    @if($overdueTasks->count() > 0)
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-red-400 mb-4">Overdue Tasks</h2>
        <div class="space-y-3">
            @foreach($overdueTasks as $task)
            <div class="bg-red-900/30 border border-red-700 rounded-lg p-4">
                <h3 class="font-medium text-white">{{ $task->title }}</h3>
                <p class="text-sm text-gray-400">Due: {{ $task->due_date->format('M d, Y') }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($tasksAboutToExpire->count() > 0)
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-yellow-400 mb-4">Expiring Soon</h2>
        <div class="space-y-3">
            @foreach($tasksAboutToExpire as $task)
            <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4">
                <h3 class="font-medium text-white">{{ $task->title }}</h3>
                <p class="text-sm text-gray-400">Due: {{ $task->due_date->format('M d, Y') }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($overdueTasks->count() === 0 && $tasksAboutToExpire->count() === 0)
    <p class="text-gray-500">No notifications at this time.</p>
    @endif
</div>
</x-dashboard.layout>
