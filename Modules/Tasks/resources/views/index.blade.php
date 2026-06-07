<x-dashboard.layout title="Tasks">
    <x-slot:scripts>
        @vite(['Modules/Tasks/resources/assets/js/tasks-board.js', 'Modules/Tasks/resources/assets/css/tasks.css'])
    </x-slot:scripts>

    @php
        $taskRoutes = [
            'boardsStore' => route('tasks.boards.store'),
            'boardUpdate' => url('/tasks/boards/__BOARD__'),
            'boardDestroy' => url('/tasks/boards/__BOARD__'),
            'columnsStore' => url('/tasks/boards/__BOARD__/columns'),
            'columnUpdate' => url('/tasks/columns/__COLUMN__'),
            'columnDestroy' => url('/tasks/columns/__COLUMN__'),
            'columnsReorder' => url('/tasks/boards/__BOARD__/columns/reorder'),
            'tasksStore' => url('/tasks/boards/__BOARD__/tasks'),
            'taskUpdate' => url('/tasks/tasks/__TASK__'),
            'taskMove' => url('/tasks/tasks/__TASK__/move'),
            'taskArchive' => url('/tasks/tasks/__TASK__/archive'),
            'taskDestroy' => url('/tasks/tasks/__TASK__'),
        ];
    @endphp

    <div
        id="tasks-app"
        class="tasks-app-shell"
        data-state='@json($state)'
        data-routes='@json($taskRoutes)'
    ></div>
</x-dashboard.layout>
