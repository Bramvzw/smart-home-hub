<x-dashboard.layout title="Find Hub">
    <div class="flex h-full flex-col items-center justify-center gap-6 p-8">
        <a
            href="{{ $url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="hub-action flex h-32 w-32 flex-col items-center justify-center gap-3 rounded-2xl text-lg font-bold"
        >
            <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            <span>Open</span>
        </a>

        <p class="max-w-sm text-center text-sm text-[var(--hub-dim)]">
            Opens Google Find Hub in a new tab. Use <strong>Play sound</strong> to ring your phone at full volume for 5 minutes — even on silent.
        </p>
    </div>
</x-dashboard.layout>
