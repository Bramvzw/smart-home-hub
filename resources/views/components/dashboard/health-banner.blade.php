@props(['module' => null])
@php
    $health = $module ? app(\App\Services\ModuleRegistry::class)->health($module) : null;
@endphp
@if ($health && ! $health->isOk())
    @php
        $needsSetup = $health->status === \App\Enums\ModuleHealthStatus::NeedsSetup;
    @endphp
    <div class="hub-health-banner" data-status="{{ $health->status->value }}" role="status">
        <svg class="hub-health-banner__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        </svg>
        <div class="hub-health-banner__body">
            <p class="hub-health-banner__title">
                {{ $needsSetup ? 'Deze module heeft nog configuratie nodig' : 'Deze module draait beperkt' }}
            </p>
            <ul class="hub-health-banner__list">
                @foreach ($health->issues as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
