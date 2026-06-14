<x-dashboard.layout title="Lighting">
    <x-slot:scripts>
        @vite([
            'Modules/Lighting/resources/assets/css/lighting.css',
            'Modules/Lighting/resources/assets/js/lighting.js',
        ])
    </x-slot:scripts>

    @php
        $flatLights = collect($lightsByProvider)->flatten(1)->values();
        $totalLights = $flatLights->count();
        $onCount = $flatLights->where('on', true)->count();
        $selectedLight = $flatLights->firstWhere('reachable', true) ?? $flatLights->first();
        $selectedKey = $selectedLight ? $selectedLight->provider.'::'.$selectedLight->id : null;
        $presetByKey = collect($presets)->keyBy('key');
        $masterPreset = $presetByKey->get($onCount > 0 ? 'off' : 'bright') ?? $presets[0] ?? null;
        $sceneSubtitles = [
            'bright' => 'Koel werklicht',
            'cozy' => 'Warm en helder',
            'movie' => 'Gedimd blauw',
            'night' => 'Zacht amber',
            'off' => 'Schakel uit',
        ];
        $normaliseColor = static fn (?string $color): ?string => filled($color) ? strtolower(str_starts_with((string) $color, '#') ? (string) $color : '#'.$color) : null;
        $reachableLights = $flatLights->where('reachable', true)->values();
        $activePreset = collect($presets)->first(function ($preset) use ($reachableLights, $normaliseColor) {
            if ($reachableLights->isEmpty()) {
                return false;
            }

            return $reachableLights->every(function ($light) use ($preset, $normaliseColor) {
                if (! $preset->power) {
                    return ! $light->on;
                }

                if (! $light->on) {
                    return false;
                }

                if ($preset->brightness !== null && $light->brightness !== $preset->brightness) {
                    return false;
                }

                if ($preset->color !== null && $light->supportsColor && $normaliseColor($light->color) !== $normaliseColor($preset->color)) {
                    return false;
                }

                return true;
            });
        });
    @endphp

    @unless($configured)
        <div class="flex h-full flex-col items-center justify-center px-6 text-center">
            <div class="mb-4 grid h-12 w-12 place-items-center rounded-[12px] bg-[var(--hub-card)] text-[var(--hub-dim)] ring-1 ring-[var(--hub-line)]">
                <x-dashboard.icons.lighting class="h-6 w-6" />
            </div>
            <h3 class="text-sm font-bold text-[var(--hub-muted)]">Geen lampen gekoppeld</h3>
            <p class="mt-1 max-w-sm text-sm text-[var(--hub-dim)]">
                Stel <code class="rounded bg-[var(--hub-card)] px-1.5 py-0.5 text-[var(--hub-muted)]">TUYA_*</code> (Calex) of
                <code class="rounded bg-[var(--hub-card)] px-1.5 py-0.5 text-[var(--hub-muted)]">GOVEE_API_KEY</code> in om je lampen te besturen.
            </p>
        </div>
    @elseif($totalLights === 0)
        <div class="flex h-full flex-col items-center justify-center px-6 text-center">
            <h3 class="text-sm font-bold text-[var(--hub-muted)]">Geen lampen gevonden</h3>
            <p class="mt-1 text-sm text-[var(--hub-dim)]">Er zijn geen bestuurbare lampen aangetroffen.</p>
        </div>
    @else
        <div
            class="lighting-console"
            data-lighting
            data-preset-url-template="{{ $presetUrlTemplate }}"
        >
            <aside class="lighting-console__rail">
                <div class="lighting-console__brand">
                    <div class="lighting-console__brand-mark">
                        <x-dashboard.icons.lighting class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="lighting-console__title">Verlichting</div>
                        <div class="lighting-console__subtitle">Smart Home Hub</div>
                    </div>
                </div>

                @if($masterPreset)
                    <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $onCount > 0 ? 'true' : 'false' }}"
                        data-preset="{{ $masterPreset->key }}"
                        data-preset-power="{{ $masterPreset->power ? 'true' : 'false' }}"
                        data-preset-brightness="{{ $masterPreset->brightness }}"
                        data-preset-color="{{ $masterPreset->color }}"
                        data-master-toggle
                        class="lighting-console__master"
                    >
                        <span class="lighting-console__master-main">
                            <span class="lighting-console__master-title" data-lighting-summary>{{ $onCount }} van {{ $totalLights }} aan</span>
                            <span class="lighting-console__master-sub">Hoofdschakelaar</span>
                        </span>
                        <span class="lighting-console__switch {{ $onCount > 0 ? '' : '' }}" aria-hidden="true" aria-checked="{{ $onCount > 0 ? 'true' : 'false' }}">
                            <span class="lighting-console__switch-dot"></span>
                        </span>
                    </button>
                @endif

                @if(count($unreachableProviders))
                    <div class="mt-4 rounded-[10px] bg-[var(--hub-danger-soft)] px-3 py-2 text-[12px] text-[var(--hub-danger)] ring-1 ring-[var(--hub-danger)]">
                        Niet bereikbaar: {{ implode(', ', $unreachableProviders) }}
                    </div>
                @endif

                <div class="lighting-console__list">
                    @foreach($lightsByProvider as $provider => $lights)
                        <div class="lighting-console__group">{{ $providerLabels[$provider] ?? $provider }}</div>
                        @foreach($lights as $light)
                            @php
                                $lightKey = $light->provider.'::'.$light->id;
                                $isSelected = $lightKey === $selectedKey;
                                $lightColor = $light->color ?? '#ffc26b';
                            @endphp
                            <article
                                class="lighting-console__row"
                                data-light
                                data-light-row
                                data-light-key="{{ $lightKey }}"
                                data-light-name="{{ $light->name }}"
                                data-provider-label="{{ $providerLabels[$provider] ?? $provider }}"
                                data-on="{{ $light->on ? 'true' : 'false' }}"
                                data-brightness="{{ $light->brightness }}"
                                data-color="{{ $lightColor }}"
                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                data-reachable="{{ $light->reachable ? 'true' : 'false' }}"
                                data-supports-color="{{ $light->supportsColor ? 'true' : 'false' }}"
                                data-url="{{ str_replace(['__PROVIDER__', '__ID__'], [$light->provider, rawurlencode($light->id)], $controlUrlTemplate) }}"
                                style="--light-color: {{ $lightColor }}; --light-brightness: {{ $light->brightness }}%;"
                            >
                                <button
                                    type="button"
                                    class="lighting-console__row-select"
                                    data-light-select
                                    data-light-key="{{ $lightKey }}"
                                    aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                                >
                                    <span class="lighting-console__swatch"></span>
                                    <span class="lighting-console__row-copy">
                                        <span class="lighting-console__row-name">{{ $light->name }}</span>
                                        <span class="lighting-console__row-state" data-light-state>
                                            @if($light->reachable)
                                                {{ $light->on ? $light->brightness.'% · '.($light->supportsColor ? 'kleur' : 'wit') : 'uit' }}
                                            @else
                                                niet bereikbaar
                                            @endif
                                        </span>
                                    </span>
                                </button>
                                <div class="lighting-console__row-power">
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $light->on ? 'true' : 'false' }}"
                                        data-action="power"
                                        @disabled(! $light->reachable)
                                        class="lighting-console__switch lighting-console__switch--small"
                                    >
                                        <span class="lighting-console__switch-dot"></span>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    @endforeach
                </div>
            </aside>

            <main class="lighting-console__main">
                <div class="lighting-console__top">
                    <div class="min-w-0">
                        <div class="lighting-console__eyebrow" data-selected-provider>{{ $providerLabels[$selectedLight->provider] ?? $selectedLight->provider }}</div>
                        <div class="lighting-console__heading" data-selected-name>{{ $selectedLight->name }}</div>
                    </div>
                    <div class="lighting-console__top-spacer"></div>
                </div>

                <section class="lighting-console__scenes" aria-label="Presets">
                    @foreach($presets as $preset)
                        <button
                            type="button"
                            data-preset="{{ $preset->key }}"
                            data-preset-power="{{ $preset->power ? 'true' : 'false' }}"
                            data-preset-brightness="{{ $preset->brightness }}"
                            data-preset-color="{{ $preset->color }}"
                            data-preset-label="{{ $preset->label }}"
                            data-active="{{ $activePreset?->key === $preset->key ? 'true' : 'false' }}"
                            aria-pressed="{{ $activePreset?->key === $preset->key ? 'true' : 'false' }}"
                            aria-label="{{ $preset->label }} toepassen"
                            class="lighting-console__scene"
                            style="--scene-color: {{ $preset->power ? ($preset->color ?? '#ffc26b') : '#7f7a72' }}"
                        >
                            <span class="lighting-console__scene-dot"></span>
                            <span>
                                <span class="lighting-console__scene-name">{{ $preset->label }}</span>
                                <span class="lighting-console__scene-sub">{{ $sceneSubtitles[$preset->key] ?? 'Preset' }}</span>
                            </span>
                            <span class="lighting-console__scene-active">Actief</span>
                        </button>
                    @endforeach
                </section>

                @foreach($lightsByProvider as $provider => $lights)
                    @foreach($lights as $light)
                        @php
                            $lightKey = $light->provider.'::'.$light->id;
                            $isSelected = $lightKey === $selectedKey;
                            $lightColor = $light->color ?? '#ffc26b';
                        @endphp
                        <section
                            class="lighting-console__stage"
                            data-light
                            data-light-panel
                            data-light-key="{{ $lightKey }}"
                            data-light-name="{{ $light->name }}"
                            data-provider-label="{{ $providerLabels[$provider] ?? $provider }}"
                            data-on="{{ $light->on ? 'true' : 'false' }}"
                            data-brightness="{{ $light->brightness }}"
                            data-color="{{ $lightColor }}"
                            data-reachable="{{ $light->reachable ? 'true' : 'false' }}"
                            data-supports-color="{{ $light->supportsColor ? 'true' : 'false' }}"
                            data-url="{{ str_replace(['__PROVIDER__', '__ID__'], [$light->provider, rawurlencode($light->id)], $controlUrlTemplate) }}"
                            style="--light-color: {{ $lightColor }}; --light-brightness: {{ $light->brightness }}%;"
                            @if(! $isSelected) hidden @endif
                        >
                            <span class="lighting-console__stage-glow"></span>
                            <div class="lighting-console__ring-wrap">
                                <div class="lighting-console__ring">
                                    @if($light->supportsColor)
                                        <input
                                            type="color"
                                            value="{{ $light->color ?? '#ffffff' }}"
                                            data-action="color"
                                            @disabled(! $light->reachable)
                                            class="lighting-console__ring-input"
                                            aria-label="Kleur voor {{ $light->name }}"
                                        >
                                    @endif
                                    <div class="lighting-console__ring-center">
                                        <x-dashboard.icons.lighting class="h-8 w-8" />
                                        <span class="lighting-console__ring-percent" data-light-percent>{{ $light->on ? $light->brightness.'%' : 'uit' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lighting-console__controls">
                                <div class="lighting-console__detail-head">
                                    <span class="lighting-console__status"></span>
                                    <span class="lighting-console__detail-name">{{ $light->name }}</span>
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $light->on ? 'true' : 'false' }}"
                                        data-action="power"
                                        @disabled(! $light->reachable)
                                        class="lighting-console__switch"
                                    >
                                        <span class="lighting-console__switch-dot"></span>
                                    </button>
                                </div>

                                <div class="lighting-console__control-block">
                                    <div class="lighting-console__label">
                                        Helderheid
                                        <span class="lighting-console__value" data-light-brightness-value>{{ $light->brightness }}%</span>
                                    </div>
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        value="{{ $light->brightness }}"
                                        data-action="brightness"
                                        @disabled(! $light->reachable)
                                        class="lighting-console__range"
                                    >
                                </div>

                                <div class="lighting-console__control-block">
                                    <div class="lighting-console__label">
                                        Lichtmodus
                                        <span class="lighting-console__value">{{ $light->supportsColor ? 'kleur' : 'wit' }}</span>
                                    </div>
                                    <div class="lighting-console__color-inline">
                                        @if($light->supportsColor)
                                            <input
                                                type="color"
                                                value="{{ $light->color ?? '#ffffff' }}"
                                                data-action="color"
                                                @disabled(! $light->reachable)
                                                class="lighting-console__native-color"
                                                aria-label="Kleur voor {{ $light->name }}"
                                            >
                                            <span class="lighting-console__meta">Gebruik de ring of swatch om de kleur te wijzigen.</span>
                                        @else
                                            <span class="lighting-console__meta">Deze lamp ondersteunt geen RGB-kleur via de provider.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </section>
                    @endforeach
                @endforeach
            </main>
        </div>
    @endunless
</x-dashboard.layout>
