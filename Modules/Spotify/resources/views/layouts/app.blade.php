<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My App')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Custom styles for dark mode */
        .dark {
            --bg-primary: #121212;
            --bg-secondary: #181818;
            --bg-card: #282828;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-color: #1DB954;
        }
        body.dark-mode {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .spotify-container {
            min-height: 100vh;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        .progress-container {
            cursor: pointer;
        }
        .dark-card {
            background-color: var(--bg-card);
            border-radius: 0.5rem;
        }
        .spotify-accent {
            color: var(--accent-color);
        }
        .spotify-accent-bg {
            background-color: var(--accent-color);
        }
        .spotify-btn {
            background-color: var(--accent-color);
            color: white;
            transition: all 0.2s;
        }
        .spotify-btn:hover {
            background-color: #1ed760;
            transform: scale(1.05);
        }
        .track-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .like-btn.active {
            color: var(--accent-color);
        }
        .next-track-container {
            position: relative;
        }
        .next-track-play-button {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.7);
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 0.25rem;
        }
        .next-track-container:hover .next-track-play-button {
            opacity: 1;
        }
    </style>
</head>
<body class="dark-mode">
<div class="spotify-container">
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
