@extends('tasks::layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="max-w-4xl mx-auto p-6 space-y-8">
        <h1 class="text-3xl font-bold text-white mb-6">Notifications</h1>

        @if($tasksAboutToExpire->isEmpty() && $overdueTasks->isEmpty())
            <p class="text-gray-300">No notifications.</p>
        @endif

        @if($tasksAboutToExpire->isNotEmpty())
            <div>
                <h2 class="text-xl font-semibold text-white mb-2">Upcoming Due Dates</h2>
                <ul class="space-y-2">
                    @foreach($tasksAboutToExpire as $task)
                        <li class="bg-gray-800 p-4 rounded-md flex justify-between items-center">
                            <span>{{ $task->title }}</span>
                            <span class="text-sm text-gray-400">{{ $task->due_date->format('M d, Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($overdueTasks->isNotEmpty())
            <div>
                <h2 class="text-xl font-semibold text-white mb-2">Overdue Tasks</h2>
                <ul class="space-y-2">
                    @foreach($overdueTasks as $task)
                        <li class="bg-gray-800 p-4 rounded-md flex justify-between items-center">
                            <span>{{ $task->title }}</span>
                            <span class="text-sm text-gray-400">{{ $task->due_date->format('M d, Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
