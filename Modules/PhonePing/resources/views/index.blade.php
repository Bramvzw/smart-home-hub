<x-dashboard.layout title="Phone">
    <x-slot:scripts>
        @vite(['Modules/PhonePing/resources/assets/js/phone-ping.js'])
    </x-slot:scripts>

    <div class="flex h-full flex-col items-center justify-center gap-6 p-8">
        @if(! $configured)
            <div class="hub-empty">
                <svg class="hub-empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3" />
                </svg>
                <h2 class="hub-empty-title">No phone configured</h2>
                <p class="hub-empty-body">Set <code class="rounded bg-[var(--hub-card)] px-1.5 py-0.5 text-[var(--hub-muted)]">PHONE_PING_NTFY_TOPIC</code> in your <code class="rounded bg-[var(--hub-card)] px-1.5 py-0.5 text-[var(--hub-muted)]">.env</code> to get started.</p>
            </div>
        @else
            <button
                id="ping-btn"
                type="button"
                data-url="{{ route('phoneping.ping') }}"
                class="hub-action flex h-32 w-32 flex-col items-center justify-center gap-3 rounded-2xl text-lg font-bold"
            >
                <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3" />
                </svg>
                <span id="ping-label">Ping</span>
            </button>

            <p id="ping-status" class="min-h-5 text-sm text-[var(--hub-dim)]"></p>
        @endif
    </div>
</x-dashboard.layout>
