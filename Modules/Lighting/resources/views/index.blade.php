<x-dashboard.layout title="Lighting" :hideHeader="true">
    <x-slot:scripts>
        @vite([
            'Modules/Lighting/resources/assets/css/lighting.css',
            'Modules/Lighting/resources/assets/js/lighting.js',
        ])
    </x-slot:scripts>

    @if(! $configured || $totalLights === 0)
        <div class="lighting">
            <header class="lighting__header">
                <h1 class="lighting__title">Lighting</h1>
            </header>
            <div class="lighting-empty">
                <span class="lighting-empty__icon" aria-hidden="true">
                    <x-dashboard.icons.lighting class="h-9 w-9" />
                </span>
                @unless($configured)
                    <h2 class="lighting-empty__title">No lights connected</h2>
                    <p class="lighting-empty__text">
                        Add <code>TUYA_*</code> for Calex or <code>GOVEE_API_KEY</code> for Govee to your <code>.env</code>, then reload this page.
                    </p>
                @else
                    <h2 class="lighting-empty__title">No lights found</h2>
                    <p class="lighting-empty__text">
                        No controllable lights were detected{{ count($unreachableProviders) ? '. Unreachable: '.implode(', ', $unreachableProviders) : '' }}.
                    </p>
                @endunless
            </div>
        </div>
    @else
        <div
            class="lighting"
            data-lighting
            data-mode="normal"
            data-preset-url-template="{{ $presetUrlTemplate }}"
            data-group-url="{{ $groupUrl }}"
            data-state-url="{{ $stateUrl }}"
            data-poll-seconds="{{ $pollSeconds }}"
        >
            <header class="lighting__header">
                <div class="lighting__heading">
                    <h1 class="lighting__title" data-lighting-title>Lighting</h1>
                    <p class="lighting__sub" data-lighting-summary>{{ $onCount }} of {{ $totalLights }} on</p>
                </div>
                <div class="lighting__actions" data-normal-only>
                    <button type="button" class="lighting-button" data-select-start>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"></rect><polyline points="8 12 11 15 16 9"></polyline></svg>
                        Select
                    </button>
                    @if($offPreset)
                        <button
                            type="button"
                            class="lighting-button lighting-button--toggle"
                            data-preset="{{ $offPreset->key }}"
                            data-preset-label="{{ $offPreset->label }}"
                            data-preset-power="false"
                            data-preset-brightness=""
                            data-preset-color=""
                            data-preset-color-temp=""
                            data-preset-target-name-contains=""
                            data-active="{{ $activePresetKey === $offPreset->key ? 'true' : 'false' }}"
                            aria-pressed="{{ $activePresetKey === $offPreset->key ? 'true' : 'false' }}"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v8"></path><path d="M6.3 6.8a8 8 0 1 0 11.4 0"></path></svg>
                            {{ $offPreset->label }}
                        </button>
                    @endif
                </div>
                <div class="lighting__actions" data-select-only hidden>
                    <button type="button" class="lighting-button" data-select-all>Select all</button>
                    <button type="button" class="lighting-button lighting-button--ghost" data-select-cancel>Cancel</button>
                </div>
            </header>

            @if(count($unreachableProviders))
                <p class="lighting__notice" role="status">Unreachable: {{ implode(', ', $unreachableProviders) }}</p>
            @endif

            <div class="lighting-strip">
                <nav class="lighting-strip__scroller" aria-label="Scenes" data-scene-strip>
                    @foreach($scenePresets as $entry)
                        @php($preset = $entry['preset'])
                        <button
                            type="button"
                            class="lighting-scene"
                            data-preset="{{ $preset->key }}"
                            data-preset-label="{{ $preset->label }}"
                            data-preset-power="{{ $preset->power ? 'true' : 'false' }}"
                            data-preset-brightness="{{ $preset->brightness }}"
                            data-preset-color="{{ $preset->color }}"
                            data-preset-color-temp="{{ $preset->colorTemp }}"
                            data-preset-target-name-contains="{{ implode(',', $preset->targetNameContains) }}"
                            data-active="{{ $activePresetKey === $preset->key ? 'true' : 'false' }}"
                            aria-pressed="{{ $activePresetKey === $preset->key ? 'true' : 'false' }}"
                        >
                            <span class="lighting-scene__dot" data-scene-dot aria-hidden="true"></span>
                            <span class="lighting-scene__copy">
                                <span class="lighting-scene__name">{{ $preset->label }}</span>
                                <span class="lighting-scene__sub">{{ $entry['subtitle'] }}</span>
                            </span>
                            <span class="lighting-scene__active">Active</span>
                        </button>
                    @endforeach
                </nav>
            </div>

            <section class="lighting-grid" aria-label="Lamps">
                @foreach($tiles as $tile)
                    @php($light = $tile['light'])
                    <article
                        class="lighting-tile"
                        data-light
                        data-light-key="{{ $tile['key'] }}"
                        data-provider="{{ $light->provider }}"
                        data-light-id="{{ $light->id }}"
                        data-light-name="{{ $light->name }}"
                        data-provider-label="{{ $tile['providerLabel'] }}"
                        data-url="{{ $tile['url'] }}"
                        data-on="{{ $light->on ? 'true' : 'false' }}"
                        data-brightness="{{ $light->brightness }}"
                        data-color="{{ $light->color }}"
                        data-mode="{{ $light->mode->value }}"
                        data-color-temp="{{ $light->colorTemp }}"
                        data-reachable="{{ $light->reachable ? 'true' : 'false' }}"
                        data-supports-color="{{ $light->supportsColor ? 'true' : 'false' }}"
                        data-supports-color-temp="{{ $light->supportsColorTemp ? 'true' : 'false' }}"
                        data-selected="false"
                    >
                        <button
                            type="button"
                            class="lighting-tile__toggle"
                            data-tile-toggle
                            aria-pressed="{{ $light->on ? 'true' : 'false' }}"
                            aria-label="{{ $light->name }}, {{ $tile['stateLabel'] }}"
                            @disabled(! $light->reachable)
                        >
                            <span class="lighting-tile__icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"></path><path d="M10 21h4"></path><path d="M12 3a6 6 0 0 0-3.6 10.8c.7.6 1.1 1.3 1.1 2.2h5c0-.9.4-1.6 1.1-2.2A6 6 0 0 0 12 3z"></path></svg>
                            </span>
                            <span class="lighting-tile__copy">
                                <span class="lighting-tile__provider">{{ $tile['providerLabel'] }}</span>
                                <span class="lighting-tile__name">{{ $light->name }}</span>
                                <span class="lighting-tile__state" data-light-state>{{ $tile['stateLabel'] }}</span>
                            </span>
                        </button>

                        <button type="button" class="lighting-tile__more" data-tile-more aria-label="Controls for {{ $light->name }}">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.9"></circle><circle cx="12" cy="12" r="1.9"></circle><circle cx="19" cy="12" r="1.9"></circle></svg>
                        </button>
                        <span class="lighting-tile__check" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="5 12 10 17 19 7"></polyline></svg>
                        </span>
                        <span class="lighting-tile__pending">
                            <span class="lighting-tile__pending-dot" aria-hidden="true"></span>
                            Sending…
                        </span>
                        <div class="lighting-tile__bar">
                            <div class="lighting-slider" data-slider>
                                <div class="lighting-slider__track" aria-hidden="true"></div>
                                <div class="lighting-slider__fill" aria-hidden="true"></div>
                                <div class="lighting-slider__thumb" aria-hidden="true"></div>
                                <input
                                    type="range"
                                    min="1"
                                    max="100"
                                    value="{{ max(1, $light->brightness) }}"
                                    class="lighting-slider__input"
                                    data-action="brightness"
                                    aria-label="Brightness of {{ $light->name }}"
                                >
                            </div>
                        </div>
                        <span class="lighting-tile__offline">Offline</span>
                    </article>
                @endforeach
            </section>

            <div class="lighting-selbar" data-selbar hidden>
                <span class="lighting-selbar__label" data-selbar-label>0 lamps selected</span>
                <button type="button" class="lighting-button lighting-button--primary" data-selbar-adjust disabled>Adjust</button>
                <button type="button" class="lighting-button lighting-button--ghost" data-select-cancel>Cancel</button>
            </div>

            <div class="lighting-toast" data-lighting-toast role="status" aria-live="polite" hidden>
                <span class="lighting-toast__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 7v6"></path><path d="M12 17h.01"></path></svg>
                </span>
                <span class="lighting-toast__text" data-lighting-toast-text></span>
                <button type="button" class="lighting-icon-button" data-toast-close aria-label="Dismiss">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"></path><path d="M18 6L6 18"></path></svg>
                </button>
            </div>

            <div class="lighting-overlay" data-sheet-overlay hidden>
                <button type="button" class="lighting-overlay__backdrop" data-sheet-close aria-label="Close controls"></button>
                <section class="lighting-sheet" aria-label="Lamp controls" data-sheet>
                    <div class="lighting-sheet__grabber" aria-hidden="true"></div>
                    <div class="lighting-sheet__head">
                        <div class="lighting-sheet__heading">
                            <h2 class="lighting-sheet__title" data-sheet-title></h2>
                            <p class="lighting-sheet__sub" data-sheet-sub></p>
                        </div>
                        <button type="button" class="lighting-switch" data-sheet-power aria-pressed="false" aria-label="Power">
                            <span class="lighting-switch__track" aria-hidden="true"><span class="lighting-switch__dot"></span></span>
                        </button>
                        <button type="button" class="lighting-icon-button lighting-icon-button--outlined" data-sheet-close aria-label="Close">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"></path><path d="M18 6L6 18"></path></svg>
                        </button>
                    </div>

                    <div class="lighting-sheet__body">
                        <div class="lighting-sheet__controls">
                            <div class="lighting-sheet__group">
                                <div class="lighting-sheet__label-row">
                                    <span class="lighting-sheet__label">Brightness</span>
                                    <span class="lighting-sheet__value" data-sheet-brightness-label>0%</span>
                                    <span class="lighting-sheet__mixed" data-sheet-mixed hidden>mixed</span>
                                </div>
                                <div class="lighting-slider lighting-slider--big" data-slider data-sheet-brightness-slider>
                                    <div class="lighting-slider__track" aria-hidden="true"></div>
                                    <div class="lighting-slider__fill" aria-hidden="true"></div>
                                    <div class="lighting-slider__thumb" aria-hidden="true"></div>
                                    <input type="range" min="1" max="100" value="50" class="lighting-slider__input" data-sheet-brightness aria-label="Brightness">
                                </div>
                            </div>

                            <div class="lighting-segmented" role="group" aria-label="Light mode" data-sheet-tabs>
                                <button type="button" class="lighting-segmented__option" data-sheet-tab="white" aria-pressed="true">White</button>
                                <button type="button" class="lighting-segmented__option" data-sheet-tab="colour" aria-pressed="false">Colour</button>
                            </div>

                            <div class="lighting-sheet__group" data-sheet-panel="white">
                                <div class="lighting-sheet__ends"><span>Warm</span><span>Cool</span></div>
                                <div class="lighting-slider lighting-slider--big lighting-slider--gradient" data-slider data-sheet-temp-slider>
                                    <div class="lighting-slider__track lighting-slider__track--temp" aria-hidden="true"></div>
                                    <div class="lighting-slider__thumb" aria-hidden="true"></div>
                                    <input type="range" min="0" max="100" value="50" class="lighting-slider__input" data-sheet-temp aria-label="Colour temperature, warm to cool">
                                </div>
                                <div class="lighting-chips">
                                    @foreach(['Warm' => 0, 'Neutral' => 50, 'Cool' => 100] as $chipLabel => $chipValue)
                                        <button type="button" class="lighting-chip" data-sheet-chip="{{ $chipValue }}" aria-pressed="false">
                                            <span class="lighting-chip__dot" data-chip-dot aria-hidden="true"></span>{{ $chipLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="lighting-sheet__group" data-sheet-panel="colour" hidden>
                                <div class="lighting-sheet__ends"><span>Pastel</span><span>Saturated</span></div>
                                <div class="lighting-slider lighting-slider--big lighting-slider--gradient" data-slider data-sheet-sat-slider>
                                    <div class="lighting-slider__track" aria-hidden="true"></div>
                                    <div class="lighting-slider__thumb" aria-hidden="true"></div>
                                    <input type="range" min="0" max="100" value="100" class="lighting-slider__input" data-sheet-sat aria-label="Saturation">
                                </div>
                                <p class="lighting-sheet__hint">Tap or drag on the ring to pick a hue.</p>
                            </div>
                        </div>

                        <div class="lighting-sheet__preview">
                            <button type="button" class="lighting-ring" data-sheet-ring aria-label="Colour ring — pick a hue" hidden>
                                <span class="lighting-ring__hole" aria-hidden="true"></span>
                                <span class="lighting-ring__preview" aria-hidden="true"></span>
                                <span class="lighting-ring__handle" aria-hidden="true"></span>
                            </button>
                            <span class="lighting-disc" data-sheet-disc aria-hidden="true"></span>
                            <span class="lighting-sheet__preview-label" data-sheet-preview-label></span>
                        </div>
                    </div>

                    <div class="lighting-sheet__footer">
                        <button type="button" class="lighting-button lighting-button--primary lighting-button--wide" data-sheet-close>Done</button>
                    </div>
                </section>
            </div>
        </div>
    @endif
</x-dashboard.layout>
