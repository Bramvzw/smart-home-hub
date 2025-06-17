<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tasks')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('scripts')
    <style>
        /* Custom styles for dark mode */
        .dark {
            --bg-primary: #121212;
            --bg-secondary: #181818;
            --bg-card: #282828;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-color: #6366F1; /* Indigo color for Tasks module */
        }
        body.dark-mode {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .tasks-container {
            min-height: 100vh;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        .tasks-accent-bg {
            background-color: var(--accent-color);
        }
        .tasks-btn {
            background-color: var(--accent-color);
            color: white;
            transition: all 0.2s;
        }
    </style>
</head>
<body class="dark-mode">
<div class="tasks-container">
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
